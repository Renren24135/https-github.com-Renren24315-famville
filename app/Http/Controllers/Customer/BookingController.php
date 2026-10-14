<?php

namespace App\Http\Controllers\Customer;

use App\Enums\BookingSource;
use App\Exceptions\BookingConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\AssetClosure;
use App\Models\BilliardRateTier;
use App\Models\BilliardTable;
use App\Models\Booking;
use App\Models\Lane;
use App\Models\Setting;
use App\Services\BookingCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->query('type', 'all');

        $bookings = auth()->user()
            ->bookings()
            ->with('bookable')
            ->when($type === 'lane', fn ($q) => $q->where('bookable_type', Lane::class))
            ->when($type === 'table', fn ($q) => $q->where('bookable_type', BilliardTable::class))
            // Most-recently-acted-on first, same as the staff bookings list
            // — a just-approved booking for next week still piles up at
            // the top rather than sorting by its scheduled session time.
            ->latest('updated_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('customer.bookings.index', compact('bookings', 'type'));
    }

    public function create(Request $request): View
    {
        $lanes = Lane::orderedByName();
        $tables = BilliardTable::orderedByName();

        $gcashQrFilename = Setting::get('gcash_qr_filename', 'gcash-qr.jpg');
        $gcashQrPath = public_path('assets/img/'.$gcashQrFilename);

        $gcash = [
            'number' => Setting::get('gcash_number', ''),
            'account_name' => Setting::get('gcash_account_name', ''),
            'qr_image' => asset('assets/img/'.$gcashQrFilename).(file_exists($gcashQrPath) ? '?v='.filemtime($gcashQrPath) : ''),
        ];

        // Lets the venue floor plan carry its selected asset straight into
        // the wizard (?asset_type=lane&asset_id=3) instead of making the
        // customer pick it again — silently ignored if absent or invalid,
        // the manual picker below still works exactly as before.
        $prefillType = $request->query('asset_type');
        $prefillId = $request->query('asset_id');

        return view('customer.bookings.create', compact('lanes', 'tables', 'gcash', 'prefillType', 'prefillId'));
    }

    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $asset = $data['asset_type'] === 'lane'
            ? Lane::find($data['lane_id'])
            : BilliardTable::find($data['table_id']);

        // `status` is a live snapshot of right now, not of the date/time
        // being booked — an occupied-right-now lane can still be reserved
        // for later today. Only maintenance is a true, time-independent
        // block; everything else is enforced by the actual time-slot
        // conflict check below (Booking::conflictsAt()).
        if (! $asset || $asset->status->value === 'maintenance') {
            return back()->withErrors(['asset_type' => 'That '.$data['asset_type'].' is under maintenance. Please choose another.'])->withInput();
        }

        if ($closure = AssetClosure::closing($asset, $data['booking_date'])) {
            return back()->withErrors(['booking_date' => "We're closed that day ({$closure->reason}). Please choose another date."])->withInput();
        }

        // The create form only visually hides the inactive field (tabs), so
        // its stray default can still arrive in the request even when it's
        // irrelevant — enforce the asset_type/field pairing here regardless
        // of what the client sent.
        $scoresheets = $asset instanceof Lane ? ($data['scoresheets'] ?? null) : null;

        // Tables offer two modes: Fixed Hours (billed upfront now, like a
        // lane) or Open Time (no hours chosen — the bill is only computed
        // from actual elapsed playtime once the session is completed).
        $isFixedTable = $asset instanceof BilliardTable && ($data['duration_mode'] ?? null) === 'fixed';
        $isOpenTable = $asset instanceof BilliardTable && ($data['duration_mode'] ?? null) === 'open';
        $hours = $isFixedTable ? ($data['hours'] ?? null) : null;

        // Open Time (and, defensively, anything else without a known
        // duration) is treated as holding the asset for the rest of the
        // day — matches how the availability timetable blocks it off.
        $durationMinutes = match (true) {
            $asset instanceof Lane => (int) $scoresheets * BookingCalculator::MINUTES_PER_SCORESHEET,
            $isFixedTable => (int) $hours * 60,
            default => null,
        };

        if ($asset instanceof BilliardTable && ! BilliardRateTier::rateForTime($data['start_time'].':00')) {
            return back()->withErrors(['start_time' => 'There is no billiard rate configured for that time. Please choose a time within our posted rate hours.'])->withInput();
        }

        $user = auth()->user();

        $baseAmount = match (true) {
            $asset instanceof Lane => BookingCalculator::amountFor($asset, $scoresheets, null, $data['start_time'].':00'),
            $isFixedTable => BookingCalculator::amountFor($asset, null, $hours, $data['start_time'].':00'),
            default => 0,
        };

        // Every booking made through this controller is an online
        // reservation, so it always carries a mandatory upfront reservation
        // fee — an Open Time table's base cost isn't known yet, so it pays a
        // flat fee instead of 10% of an unknown amount.
        $fee = $isOpenTable
            ? BookingCalculator::openTableReservationFee(BookingSource::Online)
            : BookingCalculator::reservationFeeFor($baseAmount, BookingSource::Online);

        // The fee is a required upfront partial payment now, not something
        // bundled into the counter bill. GCash is "paid" the moment a
        // reference number is submitted here, but still needs a human to
        // actually check it landed before the booking can be approved.
        $feeMethod = $data['reservation_fee_method'];
        $feeStatus = $feeMethod === 'gcash' ? 'awaiting_verification' : 'unpaid';

        // The conflict re-check and the insert happen inside one locked
        // transaction — locking the asset row (not the bookings table)
        // serializes two customers racing for the SAME lane/table without
        // blocking anyone booking a different one. Without this, two
        // requests could both pass conflictsAt() before either INSERTs.
        try {
            $booking = DB::transaction(function () use ($asset, $data, $durationMinutes, $user, $scoresheets, $hours, $baseAmount, $fee, $feeMethod, $feeStatus) {
                $locked = $asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();

                if (Booking::conflictsAt($locked::class, $locked->id, $data['booking_date'], $data['start_time'].':00', $durationMinutes)) {
                    throw new BookingConflictException($locked->name);
                }

                return Booking::create([
                    'bookable_type' => $locked::class,
                    'bookable_id' => $locked->id,
                    'user_id' => $user->id,
                    'customer_name' => $user->name,
                    'customer_phone' => $user->phone,
                    'source' => 'online',
                    'status' => 'pending',
                    'booking_date' => $data['booking_date'],
                    'start_time' => $data['start_time'],
                    'scoresheets' => $scoresheets,
                    'hours' => $hours,
                    'total_amount' => round($baseAmount + $fee, 2),
                    'reservation_fee' => $fee,
                    'reservation_fee_method' => $feeMethod,
                    'reservation_fee_reference' => $feeMethod === 'gcash' ? ($data['reservation_fee_reference'] ?? null) : null,
                    'reservation_fee_status' => $feeStatus,
                    'balance_payment_method' => $data['balance_payment_method'],
                    'balance_payment_reference' => $data['balance_payment_method'] === 'gcash' ? ($data['balance_payment_reference'] ?? null) : null,
                    'payment_status' => 'unpaid',
                ]);
            });
        } catch (BookingConflictException $e) {
            return back()->withErrors(['start_time' => $e->assetName.' is already reserved at that time. Please choose another lane/table, or pick a free time from the timetable above.'])->withInput();
        }

        // The booking exists and the slot is held either way — PayMongo
        // only changes what happens next: an instant hosted GCash checkout
        // instead of "type in a reference and wait for a cashier."
        if ($request->boolean('pay_via_paymongo')) {
            return redirect()->route('paymongo.pay-fee', $booking);
        }

        $statusMessage = "Booking submitted — your reference is {$booking->bookingNo()}. We'll verify your GCash payment before approving — you'll be notified once approved.";

        return redirect()->route('customer.bookings.index')->with('status', $statusMessage);
    }

    public function show(Booking $booking): View
    {
        abort_unless($booking->user_id === auth()->id(), 403);

        $booking->load('transactions');

        return view('customer.bookings.show', compact('booking'));
    }

    public function cancel(Booking $booking): RedirectResponse
    {
        abort_unless($booking->user_id === auth()->id(), 403);
        abort_unless(in_array($booking->status->value, ['pending', 'approved']), 403);

        $booking->update(['status' => 'cancelled', 'cancel_reason' => 'Cancelled by customer']);

        return redirect()->route('customer.bookings.index')->with('status', 'Booking cancelled.');
    }
}
