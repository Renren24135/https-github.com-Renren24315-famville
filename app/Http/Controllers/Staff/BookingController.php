<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBookingStatusRequest;
use App\Mail\BookingApprovedMail;
use App\Mail\BookingVoidedMail;
use App\Mail\BookingVoidRequestedMail;
use App\Models\BilliardTable;
use App\Models\Booking;
use App\Models\Lane;
use App\Models\Transaction;
use App\Services\BookingCalculator;
use App\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage-bookings');

        $status = $request->query('status', 'pending');
        $type = $request->query('type', 'all');
        $isNewBookings = $request->query('when') === 'soon';
        $isAdmin = auth()->user()->hasRole('admin');
        $userId = auth()->id();
        $search = trim((string) $request->query('q', ''));

        $bookings = Booking::query()
            ->with(['bookable', 'customer', 'transactions'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($type === 'lane', fn ($q) => $q->where('bookable_type', Lane::class))
            ->when($type === 'table', fn ($q) => $q->where('bookable_type', BilliardTable::class))
            // Search by customer name or booking reference (BK-000123) —
            // mainly for finding an online customer who still owes their
            // session payment in the Approved tab.
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('customer_name', 'like', "%{$search}%");
                    if (preg_match('/^(?:bk-?)?0*(\d+)$/i', $search, $m)) {
                        $q2->orWhere('id', (int) $m[1]);
                    }
                });
            })
            // "New Booking" is an alert queue, not a history log — online
            // reservations landing today or tomorrow that still need a
            // cashier to act on them, soonest first.
            ->when($isNewBookings, fn ($q) => $q->where('source', 'online')
                ->whereBetween('booking_date', [now()->toDateString(), now()->addDay()->toDateString()]))
            // Each cashier only sees bookings they personally handled — an
            // unclaimed pending online reservation is the one exception,
            // shown to every cashier as a shared queue until someone
            // approves (claims) it.
            ->when(! $isAdmin, function ($q) use ($userId) {
                $q->where(function ($q2) use ($userId) {
                    $q2->where(function ($q3) {
                        $q3->where('status', 'pending')->where('source', 'online');
                    })
                        ->orWhere('approved_by', $userId)
                        ->orWhere('created_by', $userId);
                });
            })
            ->when($isNewBookings, function ($q) {
                $q->oldest('booking_date')->oldest('start_time');
            }, function ($q) {
                // Most-recently-acted-on first — a just-approved booking
                // for next week should still pile up at the top, not sort
                // by its scheduled session time.
                $q->latest('updated_at')->latest('id');
            })
            ->paginate(15)
            ->withQueryString();

        return view('staff.bookings.index', compact('bookings', 'status', 'type', 'isAdmin', 'isNewBookings', 'search'));
    }

    /**
     * Polled from every staff/admin page (see layouts/app.blade.php) so a
     * new online reservation shows up as a toast within seconds instead of
     * needing a manual page refresh. Scoped to just the shared unclaimed
     * queue — pending, online, created after the client's last check —
     * since that's the one thing every cashier needs to know about the
     * instant it lands, regardless of who ends up claiming it.
     */
    public function notifications(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('manage-bookings');

        $since = $request->query('since');
        $serverTime = now()->toIso8601String();

        $bookings = Booking::query()
            ->with('bookable')
            ->where('status', 'pending')
            ->where('source', 'online')
            ->when($since, fn ($q) => $q->where('created_at', '>', $since))
            ->oldest('created_at')
            ->limit(20)
            ->get()
            ->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'booking_no' => $booking->bookingNo(),
                'customer_name' => $booking->customer_name,
                'asset_label' => $booking->assetLabel(),
                'booking_date' => $booking->booking_date->format('M d, Y'),
                'start_time' => \Illuminate\Support\Carbon::parse($booking->start_time)->format('g:i A'),
            ]);

        return response()->json([
            'server_time' => $serverTime,
            'new' => $bookings,
        ]);
    }

    public function updateStatus(UpdateBookingStatusRequest $request, Booking $booking): RedirectResponse
    {
        $data = $request->validated();
        $asset = $booking->bookable;
        $user = auth()->user();

        // A cashier can act on an unclaimed pending online reservation (to
        // claim it via approve/reject) or on a booking they already claimed
        // — never on another cashier's. Admin is unrestricted.
        if (! $user->hasRole('admin')) {
            $isUnclaimed = $booking->status->value === 'pending' && $booking->source->value === 'online';
            $isOwnedByUser = $booking->approved_by === $user->id || $booking->created_by === $user->id;

            abort_unless($isUnclaimed || $isOwnedByUser, 403, 'This booking is being handled by another cashier.');
        }

        switch ($data['status']) {
            case 'approved':
                abort_if(! $booking->reservationFeeSettled(), 422, 'The 10% reservation fee must be confirmed before this booking can be approved.');

                $booking->update([
                    'status' => 'approved',
                    'cancel_reason' => null,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);

                if ($booking->user_id && $booking->customer?->email) {
                    $this->queueMailSafely(fn () => Mail::to($booking->customer->email)->queue(new BookingApprovedMail($booking->fresh(['bookable', 'customer']))));
                }
                break;

            case 'occupied':
                // Lanes and Fixed Hours tables know their price upfront, so
                // the cashier must collect that payment before the session
                // starts — an Open Time table's price isn't known until it
                // finishes, so it's exempt and still pays at checkout.
                $isOpenTimeTable = $asset instanceof BilliardTable && $booking->hours === null;
                abort_if(! $isOpenTimeTable && $booking->payment_status->value !== 'paid', 422, 'Payment must be collected before starting the session.');

                BookingCalculator::startSession($booking);
                break;

            case 'confirm_balance_payment':
                // Mirrors confirm_reservation_fee, but for the session price
                // itself — cashier verifies a GCash reference the customer
                // already sent for the balance, before ever starting the
                // session (walk-in-preference/cash balances are instead
                // collected via the POS, which also gates the same way).
                // Allowed at pending too — a customer can send the session's
                // GCash reference at booking time, and the cashier shouldn't
                // have to approve the reservation fee first just to verify it.
                abort_if(! in_array($booking->status->value, ['pending', 'approved'], true), 422, 'Only a pending or approved booking\'s balance payment can be confirmed.');
                abort_if($booking->payment_status->value === 'paid', 422, 'This booking is already paid.');
                abort_if($booking->balance_payment_method?->value !== 'gcash', 422, 'This booking\'s balance isn\'t marked for GCash payment.');
                abort_if($asset instanceof BilliardTable && $booking->hours === null, 422, 'Open Time bookings are billed at checkout, once the session ends.');

                // The transaction row and the payment_status flip commit or
                // roll back together — see Feature 1 of the security pass
                // for why (a crash between the two would leave a booking
                // that looks unpaid with a receipt already on file).
                BookingCalculator::confirmBalancePaid($booking, auth()->id(), 'gcash', $booking->balance_payment_reference);
                break;

            case 'confirm_reservation_fee':
                // Cashier/admin confirms the 10% fee actually landed — for
                // GCash, checked against their own GCash app; for walk-in,
                // cash physically received at the counter. This is what
                // unlocks the Approve button; nothing else changes status.
                abort_if((float) $booking->reservation_fee <= 0, 422, 'This booking has no reservation fee to confirm.');
                abort_if($booking->status->value !== 'pending', 422, 'Only a pending booking\'s reservation fee can be confirmed.');

                // Record it as a real transaction too, so it shows up in
                // revenue reports/cash counts the same as any other payment
                // — not just a flag on the booking — and gives the customer
                // a receipt for the fee they already paid. Both writes are
                // one atomic unit (see Feature 1 note above).
                BookingCalculator::confirmReservationFeePaid(
                    $booking,
                    auth()->id(),
                    $booking->reservation_fee_method?->value === 'gcash' ? 'gcash' : 'cash',
                    $booking->reservation_fee_reference
                );
                break;

            case 'confirm_gcash_payment':
                // One-click verification for a pending booking that has
                // GCash references awaiting confirmation on the reservation
                // fee, the session balance, or both — the customer sent
                // them as a single decision, so they're recorded as one
                // transaction in payment history instead of two.
                abort_if($booking->status->value !== 'pending', 422, 'Only a pending booking\'s GCash payment can be confirmed this way.');

                $isOpenTimeTable = $asset instanceof BilliardTable && $booking->hours === null;
                $feePendingGcash = (float) $booking->reservation_fee > 0
                    && $booking->reservation_fee_status->value !== 'paid'
                    && $booking->reservation_fee_method?->value === 'gcash';
                $balancePendingGcash = ! $isOpenTimeTable
                    && $booking->payment_status->value === 'unpaid'
                    && $booking->balance_payment_method?->value === 'gcash'
                    && $booking->balance_payment_reference;

                abort_unless($feePendingGcash || $balancePendingGcash, 422, 'There is no GCash payment awaiting verification on this booking.');

                $amount = 0;
                $refs = [];
                $updates = [
                    'cancel_reason' => null,
                    'approved_by' => auth()->id(),
                ];

                if ($feePendingGcash) {
                    $amount += (float) $booking->reservation_fee;
                    $refs[] = $booking->reservation_fee_reference;
                    $updates['reservation_fee_status'] = 'paid';
                    $updates['reservation_fee_paid_at'] = now();
                }
                if ($balancePendingGcash) {
                    $amount += (float) $booking->remainingBalance();
                    $refs[] = $booking->balance_payment_reference;
                    $updates['payment_status'] = 'paid';
                }

                // The fee is the only thing gating approval — once it's
                // settled (paid just now, or already paid in cash earlier),
                // verifying GCash can approve the booking in the same click
                // instead of leaving the cashier a second "Approve" step.
                $feeWillBeSettled = (float) $booking->reservation_fee <= 0
                    || ($updates['reservation_fee_status'] ?? $booking->reservation_fee_status->value) === 'paid';

                if ($feeWillBeSettled) {
                    $updates['status'] = 'approved';
                    $updates['approved_at'] = now();
                }

                DB::transaction(function () use ($booking, $updates, $amount, $refs) {
                    $booking->update($updates);

                    Transaction::createWithReceipt([
                        'booking_id' => $booking->id,
                        'user_id' => $booking->user_id,
                        'cashier_id' => auth()->id(),
                        'amount' => $amount,
                        'cash_tendered' => null,
                        'change_due' => 0,
                        'payment_method' => 'gcash',
                        'gcash_reference' => implode(' / ', array_filter($refs)),
                        'status' => 'paid',
                    ]);
                });

                if ($feeWillBeSettled && $booking->user_id && $booking->customer?->email) {
                    $this->queueMailSafely(fn () => Mail::to($booking->customer->email)->queue(new BookingApprovedMail($booking->fresh(['bookable', 'customer']))));
                }
                break;

            case 'completed':
                if ($asset instanceof BilliardTable && $booking->hours === null) {
                    // Open Time table: no fixed duration was chosen, so the
                    // bill is only known now, from actual elapsed playtime.
                    BookingCalculator::finishTableSession($booking);
                } else {
                    $booking->update(['status' => 'completed']);
                    $asset->update(['status' => 'available']);
                }
                break;

            case 'void_pending':
                // Unified stand-in for both "reject" (pending) and "cancel"
                // (approved) — nothing is ever rejected/cancelled outright
                // by a cashier anymore. This just flags it and holds the
                // slot until admin decides.
                abort_if(! in_array($booking->status->value, ['pending', 'approved'], true), 422, 'This booking can no longer be voided.');

                $booking->update([
                    'status' => 'void_pending',
                    'cancel_reason' => $data['cancel_reason'],
                    // Otherwise an unclaimed pending booking (no approved_by
                    // yet) would vanish from every cashier's view the moment
                    // it's voided — the cashier who flagged it should still
                    // be able to see it sitting in Void Pending.
                    'approved_by' => $booking->approved_by ?: auth()->id(),
                ]);

                $recipients = ReportService::recipientEmails();
                if ($recipients->isNotEmpty()) {
                    $this->queueMailSafely(fn () => Mail::to($recipients->first())
                        ->cc($recipients->skip(1)->all())
                        ->queue(new BookingVoidRequestedMail($booking->fresh(['bookable', 'customer', 'approver']))));
                }
                break;

            case 'void_approved':
                // Only admin finalizes a void — and finalizing means the
                // transaction is removed entirely, not just marked
                // cancelled, with the customer notified it's gone.
                abort_unless($user->hasRole('admin'), 403);
                abort_if($booking->status->value !== 'void_pending', 422, 'This booking is not awaiting a void decision.');

                // Persisted (briefly, before deletion) so it's captured in
                // the activity log for audit purposes. The reason-update and
                // the delete are one atomic step — nothing should observe
                // the reason saved without the booking actually being gone.
                $booking->load(['bookable', 'customer']);

                if ($booking->user_id && $booking->customer?->email) {
                    // Plain values, not the model — the booking is deleted
                    // moments from now, and a queued job that re-fetches it
                    // by id (as SerializesModels does) would fail once it's
                    // gone.
                    $customerEmail = $booking->customer->email;
                    $customerName = $booking->customer->name ?? $booking->customer_name;
                    $assetLabel = $booking->assetLabel();
                    $bookingDate = $booking->booking_date->format('F d, Y');
                    $scheduledTime = \Illuminate\Support\Carbon::parse($booking->start_time)->format('g:i A');
                    $reason = $data['void_admin_reason'];

                    $this->queueMailSafely(fn () => Mail::to($customerEmail)
                        ->queue(new BookingVoidedMail($customerName, $assetLabel, $bookingDate, $scheduledTime, $reason)));
                }

                DB::transaction(function () use ($booking, $data) {
                    $booking->update(['void_admin_reason' => $data['void_admin_reason']]);
                    $booking->delete();
                });

                return back()->with('status', 'Booking voided and removed. Customer notified.');

            case 'void_denied':
                // Admin keeps the reservation — it goes back to pending,
                // unclaimed, so it re-enters the normal approval queue
                // rather than being silently re-approved on the void's say-so.
                abort_unless($user->hasRole('admin'), 403);
                abort_if($booking->status->value !== 'void_pending', 422, 'This booking is not awaiting a void decision.');

                $booking->update([
                    'status' => 'pending',
                    'cancel_reason' => null,
                    'approved_by' => null,
                    'approved_at' => null,
                ]);
                break;
        }

        return back()->with('status', 'Booking updated.');
    }
}
