<?php

namespace App\Http\Controllers\Staff;

use App\Exceptions\BookingConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessPaymentRequest;
use App\Http\Requests\StoreWalkInBookingRequest;
use App\Models\AssetClosure;
use App\Models\BilliardRateTier;
use App\Models\BilliardTable;
use App\Models\Booking;
use App\Models\Lane;
use App\Models\Transaction;
use App\Services\BookingCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(): View
    {
        $this->authorize('process-pos');

        $isAdmin = auth()->user()->hasRole('admin');
        $userId = auth()->id();

        // Lanes and fixed-hours tables are billed upfront, so they're payable
        // as soon as they're approved — payment must be collected before the
        // cashier is allowed to start the session. An Open Time table (no
        // fixed hours chosen) isn't payable until it's completed — the bill
        // isn't known until then. Walk-ins with a known price are paid
        // instantly when the session starts (see storeWalkIn()), so this
        // left-hand list ends up being online reservations plus any Open
        // Time walk-in that has since finished.
        $pending = Booking::query()
            ->with('bookable')
            ->where('payment_status', 'unpaid')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('bookable_type', Lane::class)->whereIn('status', ['approved', 'occupied', 'completed']);
                })->orWhere(function ($q2) {
                    $q2->where('bookable_type', BilliardTable::class)
                        ->where(function ($q3) {
                            $q3->where('status', 'completed')
                                ->orWhere(function ($q4) {
                                    $q4->whereIn('status', ['approved', 'occupied'])->whereNotNull('hours');
                                });
                        });
                });
            })
            // A cashier only collects payment for sessions they personally
            // handled (approved/started) — never another cashier's.
            ->when(! $isAdmin, function ($q) use ($userId) {
                $q->where(function ($q2) use ($userId) {
                    $q2->where('approved_by', $userId)->orWhere('created_by', $userId);
                });
            })
            ->latest('session_started_at')
            ->get();

        // Live walk-in sessions currently in progress, shown on the right so
        // their playtime stays out of the left payment-collection column —
        // a walk-in's own transaction was already settled the moment it
        // started (or, for Open Time, will be once it lands in $pending).
        $activeWalkIns = Booking::query()
            ->with('bookable')
            ->where('source', 'walkin')
            ->where('status', 'occupied')
            ->when(! $isAdmin, fn ($q) => $q->where('created_by', $userId))
            ->latest('session_started_at')
            ->get();

        $lanes = Lane::orderedByName();
        $tables = BilliardTable::orderedByName();

        return view('staff.pos.index', compact('pending', 'activeWalkIns', 'lanes', 'tables'));
    }

    public function storeWalkIn(StoreWalkInBookingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $now = now();

        if ($now->format('H:i:s') < '14:00:00' || $now->format('H:i:s') > '22:00:00') {
            return back()->withErrors(['asset_type' => 'Walk-ins can only be logged during operating hours (2:00 PM - 10:00 PM).'])->withInput();
        }

        $asset = $data['asset_type'] === 'lane'
            ? Lane::find($data['lane_id'])
            : BilliardTable::find($data['table_id']);

        if (! $asset || $asset->status->value !== 'available') {
            return back()->withErrors(['asset_type' => 'That '.$data['asset_type'].' is no longer available. Please choose another.'])->withInput();
        }

        if ($closure = AssetClosure::closing($asset, $now->toDateString())) {
            return back()->withErrors(['asset_type' => "This {$data['asset_type']} is closed today ({$closure->reason})."])->withInput();
        }

        // Same defensive normalization as the customer form: only the field
        // matching the actual asset type is kept, regardless of what the
        // (visually-toggled, not disabled) form fields sent. A table's hours
        // is only set when Fixed Hours was chosen — Open Time leaves it null,
        // and the bill is computed from actual elapsed playtime at checkout.
        $scoresheets = $asset instanceof Lane ? ($data['scoresheets'] ?? null) : null;
        $isFixedTable = $asset instanceof BilliardTable && ($data['duration_mode'] ?? null) === 'fixed';
        $hours = $isFixedTable ? ($data['hours'] ?? null) : null;
        $knowsPriceUpfront = $asset instanceof Lane || $isFixedTable;

        $durationMinutes = match (true) {
            $asset instanceof Lane => (int) $scoresheets * BookingCalculator::MINUTES_PER_SCORESHEET,
            $isFixedTable => (int) $hours * 60,
            default => null,
        };

        if ($asset instanceof BilliardTable && ! BilliardRateTier::rateForTime($now->format('H:i:s'))) {
            return back()->withErrors(['asset_type' => 'There is no billiard rate configured for the current time. Please add a matching rate tier first.'])->withInput();
        }

        // Locked transaction, same reasoning as the customer booking flow —
        // a walk-in and an online booking (or two walk-ins) could otherwise
        // both slip past conflictsAt() for the same asset at the same
        // instant.
        $cashierId = auth()->id();
        try {
            $booking = DB::transaction(function () use ($asset, $data, $now, $durationMinutes, $scoresheets, $hours, $cashierId) {
                $locked = $asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();

                if (Booking::conflictsAt($locked::class, $locked->id, $now->toDateString(), $now->format('H:i:s'), $durationMinutes)) {
                    throw new BookingConflictException($locked->name);
                }

                return Booking::create([
                    'bookable_type' => $locked::class,
                    'bookable_id' => $locked->id,
                    'user_id' => null,
                    'customer_name' => $data['customer_name'],
                    'customer_phone' => $data['customer_phone'] ?? null,
                    'source' => 'walkin',
                    'status' => 'pending',
                    'booking_date' => $now->toDateString(),
                    'start_time' => $now->format('H:i:s'),
                    'scoresheets' => $scoresheets,
                    'hours' => $hours,
                    'payment_status' => 'unpaid',
                    'created_by' => $cashierId,
                    'approved_by' => $cashierId,
                    'approved_at' => $now,
                ]);
            });
        } catch (BookingConflictException $e) {
            return back()->withErrors(['asset_type' => 'That '.$data['asset_type'].' already has a booking right now.'])->withInput();
        }

        BookingCalculator::startSession($booking->fresh());

        // A lane or Fixed Hours table's price is already known the instant
        // the session starts, so the cashier collects it right here — no
        // separate trip through the POS payment list needed. An Open Time
        // table still can't be billed until it finishes.
        if ($knowsPriceUpfront) {
            $booking->refresh();
            $isGcash = $data['payment_method'] === 'gcash';
            $tendered = $isGcash ? (float) $booking->total_amount : (float) $data['cash_tendered'];

            // The transaction record and the payment_status flip must land
            // together — a crash between the two would otherwise leave a
            // booking that looks unpaid with a receipt already on file, or
            // vice versa.
            DB::transaction(function () use ($booking, $data, $isGcash, $tendered) {
                Transaction::createWithReceipt([
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'cashier_id' => auth()->id(),
                    'amount' => $booking->total_amount,
                    'cash_tendered' => $isGcash ? null : $tendered,
                    'change_due' => $isGcash ? 0 : round($tendered - (float) $booking->total_amount, 2),
                    'payment_method' => $data['payment_method'],
                    'gcash_reference' => $isGcash ? $data['gcash_reference'] : null,
                    'status' => 'paid',
                ]);

                $booking->update(['payment_status' => 'paid']);
            });
        }

        return back()->with('status', 'Walk-in session started for '.$asset->name.'.');
    }

    public function pay(ProcessPaymentRequest $request, Booking $booking): RedirectResponse
    {
        abort_if($booking->payment_status === \App\Enums\PaymentStatus::Paid, 422, 'Already paid.');

        $user = auth()->user();
        abort_unless(
            $user->hasRole('admin') || $booking->approved_by === $user->id || $booking->created_by === $user->id,
            403,
            'This booking is being handled by another cashier.'
        );

        $data = $request->validated();
        $cashierId = auth()->id();

        // Locked so two cashiers (or a double-click) can't both collect
        // payment on the same booking — the row is re-checked for
        // payment_status inside the lock, not just before it.
        $transaction = DB::transaction(function () use ($booking, $data, $cashierId) {
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            abort_if($locked->payment_status === \App\Enums\PaymentStatus::Paid, 422, 'Already paid.');

            $amountDue = $locked->remainingBalance();
            $isGcash = $data['payment_method'] === 'gcash';
            $tendered = $isGcash ? $amountDue : (float) $data['cash_tendered'];

            $transaction = Transaction::createWithReceipt([
                'booking_id' => $locked->id,
                'user_id' => $locked->user_id,
                'cashier_id' => $cashierId,
                'amount' => $amountDue,
                'cash_tendered' => $isGcash ? null : $tendered,
                'change_due' => $isGcash ? 0 : round($tendered - $amountDue, 2),
                'payment_method' => $data['payment_method'],
                'gcash_reference' => $isGcash ? $data['gcash_reference'] : null,
                'status' => 'paid',
            ]);

            $locked->update(['payment_status' => 'paid']);

            return $transaction;
        });

        return redirect()->route('staff.pos.receipt', $transaction)->with('status', 'Payment recorded.');
    }

    public function receipt(Transaction $transaction): View
    {
        $this->authorize('process-pos');

        $transaction->load(['booking.bookable', 'cashier', 'customer']);

        // A booking paid in two parts (reservation fee + session balance)
        // gets one combined receipt listing every payment collected for
        // it, not just whichever row's "View" link was clicked — mirrors
        // how Payment History already folds them into a single line.
        $transactions = $transaction->booking
            ? $transaction->booking->transactions()->with('cashier')->oldest()->get()
            : collect([$transaction]);

        abort_unless(
            auth()->user()->hasRole('admin') || $transactions->contains('cashier_id', auth()->id()),
            403
        );

        return view('staff.pos.receipt', compact('transaction', 'transactions'));
    }
}
