<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\BilliardTable;
use App\Models\Booking;
use App\Models\Lane;
use App\Services\BookingCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(): View
    {
        $bookings = auth()->user()
            ->bookings()
            ->with('bookable')
            ->latest('booking_date')
            ->latest('start_time')
            ->paginate(10);

        return view('customer.bookings.index', compact('bookings'));
    }

    public function create(): View
    {
        $lanes = Lane::query()->orderBy('name')->get();
        $tables = BilliardTable::query()->orderBy('name')->get();

        return view('customer.bookings.create', compact('lanes', 'tables'));
    }

    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $asset = $data['asset_type'] === 'lane'
            ? Lane::findOrFail($data['asset_id'])
            : BilliardTable::findOrFail($data['asset_id']);

        if ($asset->status !== \App\Enums\AssetStatus::Available) {
            return back()->withErrors(['asset_id' => 'That '.$data['asset_type'].' is not currently available for booking.'])->withInput();
        }

        $conflict = $asset->bookings()
            ->whereDate('booking_date', $data['booking_date'])
            ->where('start_time', $data['start_time'])
            ->whereIn('status', ['pending', 'approved', 'occupied'])
            ->exists();

        if ($conflict) {
            return back()->withErrors(['start_time' => 'That time slot is already booked. Please choose another.'])->withInput();
        }

        if ($asset instanceof BilliardTable && ! \App\Models\BilliardRateTier::rateForTime($data['start_time'].':00')) {
            return back()->withErrors(['start_time' => 'There is no billiard rate configured for that time. Please choose a time within our posted rate hours.'])->withInput();
        }

        $user = auth()->user();

        $amount = BookingCalculator::amountFor(
            $asset,
            $data['scoresheets'] ?? null,
            $data['hours'] ?? null,
            $data['start_time'].':00'
        );

        Booking::create([
            'bookable_type' => $asset::class,
            'bookable_id' => $asset->id,
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
            'source' => 'online',
            'status' => 'pending',
            'booking_date' => $data['booking_date'],
            'start_time' => $data['start_time'],
            'scoresheets' => $data['scoresheets'] ?? null,
            'hours' => $data['hours'] ?? null,
            'total_amount' => $amount,
            'payment_status' => 'unpaid',
        ]);

        return redirect()->route('customer.bookings.index')
            ->with('status', 'Booking request submitted. Please wait for cashier approval.');
    }

    public function show(Booking $booking): View
    {
        abort_unless($booking->user_id === auth()->id(), 403);

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
