<?php

namespace App\Services;

use App\Enums\BookingSource;
use App\Models\BilliardRateTier;
use App\Models\BilliardTable;
use App\Models\Booking;
use App\Models\Lane;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingCalculator
{
    /**
     * Assumed playtime per bowling scoresheet, used to compute the lane's
     * session end time (shown as a static "in use X – Y" range, not a
     * countdown).
     */
    public const MINUTES_PER_SCORESHEET = 60;

    /**
     * Online bookings carry a non-refundable reservation fee on top of the
     * session cost, since the slot is held in advance — walk-ins pay only
     * for the session itself.
     */
    public const RESERVATION_FEE_RATE = 0.10;

    /**
     * An Open Time table has no known price at booking time, so its
     * reservation fee can't be a percentage of anything yet — it's still a
     * mandatory upfront partial payment, just a flat amount instead of 10%.
     */
    public const OPEN_TABLE_RESERVATION_FEE = 20.0;

    public static function amountFor(Lane|BilliardTable $asset, ?int $scoresheets, ?int $hours, string $startTime): float
    {
        if ($asset instanceof Lane) {
            return round((float) $asset->price_per_scoresheet * (int) $scoresheets, 2);
        }

        $tier = BilliardRateTier::rateForTime($startTime);
        $rate = $tier?->rate_per_hour ?? 0;

        return round((float) $rate * (int) $hours, 2);
    }

    public static function reservationFeeFor(float $baseAmount, BookingSource $source): float
    {
        if ($source !== BookingSource::Online) {
            return 0.0;
        }

        return round($baseAmount * self::RESERVATION_FEE_RATE, 2);
    }

    public static function openTableReservationFee(BookingSource $source): float
    {
        return $source === BookingSource::Online ? self::OPEN_TABLE_RESERVATION_FEE : 0.0;
    }

    public static function sessionMinutes(Lane|BilliardTable $asset, ?int $scoresheets, ?int $hours): int
    {
        if ($asset instanceof Lane) {
            return (int) $scoresheets * self::MINUTES_PER_SCORESHEET;
        }

        return (int) $hours * 60;
    }

    /**
     * Start the live session on a booking and flip the asset + booking to
     * occupied. Lanes are always billed upfront by scoresheets purchased.
     * Billiard tables support two modes, chosen at booking time:
     *  - Fixed hours (booking->hours is set): billed upfront just like a
     *    lane, with a real estimated end time.
     *  - Open Time (booking->hours is null): no fixed duration — the player
     *    stops whenever they like, so no end time or bill can be known yet;
     *    that's computed later by finishTableSession() from actual elapsed
     *    playtime.
     */
    public static function startSession(Booking $booking): void
    {
        // The booking and the asset flip together — a crash between the
        // two would otherwise leave an "occupied" lane with no actual
        // session recorded, or vice versa.
        DB::transaction(function () use ($booking) {
            $asset = $booking->bookable;
            $startedAt = Carbon::now();
            $isOpenTimeTable = $asset instanceof BilliardTable && $booking->hours === null;

            if ($isOpenTimeTable) {
                // The flat reservation fee was already collected upfront at
                // booking time — leave it (and the total_amount that already
                // includes it) alone; finishTableSession() adds the real
                // session cost on top once playtime is known.
                $booking->update([
                    'status' => 'occupied',
                    'session_started_at' => $startedAt,
                    'session_ends_at' => null,
                ]);
            } else {
                $minutes = self::sessionMinutes($asset, $booking->scoresheets, $booking->hours);

                // Price using the booking's own scheduled start time, not the
                // exact moment the cashier clicks "Start Session" — that time was
                // already validated against the rate tiers when the booking was
                // created, and recomputing off the live clock can drift outside
                // every tier and silently zero out the bill.
                $rateTime = $booking->start_time ?: $startedAt->format('H:i:s');
                $baseAmount = self::amountFor($asset, $booking->scoresheets, $booking->hours, $rateTime);
                $fee = self::reservationFeeFor($baseAmount, $booking->source);

                $booking->update([
                    'status' => 'occupied',
                    'session_started_at' => $startedAt,
                    'session_ends_at' => $startedAt->copy()->addMinutes($minutes),
                    'total_amount' => round($baseAmount + $fee, 2),
                    'reservation_fee' => $fee,
                ]);
            }

            $asset->update(['status' => 'occupied']);
        });
    }

    /**
     * Close out an open-ended billiard table session: bills for the actual
     * elapsed playtime (any started hour counts as a full hour, standard
     * billiard-hall practice) at the rate in effect when play began.
     */
    public static function finishTableSession(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $asset = $booking->bookable;
            $startedAt = $booking->session_started_at;
            $finishedAt = Carbon::now();

            $minutesPlayed = max(1, $startedAt->diffInMinutes($finishedAt));
            $hours = (int) ceil($minutesPlayed / 60);

            $baseAmount = self::amountFor($asset, null, $hours, $startedAt->format('H:i:s'));

            // The flat Open Time reservation fee (booking->reservation_fee) was
            // already collected upfront at booking time — just add the real
            // session cost on top, don't recompute or re-add a fee.
            $fee = (float) $booking->reservation_fee;

            $booking->update([
                'status' => 'completed',
                'hours' => $hours,
                'total_amount' => round($baseAmount + $fee, 2),
            ]);

            $asset->update(['status' => 'available']);
        });
    }

    /**
     * Mark a booking's reservation fee as paid and record the transaction
     * — the same two writes `Staff\BookingController::updateStatus()`'s
     * `confirm_reservation_fee` case performs for a cashier's manual
     * confirmation, extracted here so the PayMongo webhook can replay the
     * exact same result for an automatic one. Does NOT auto-approve the
     * booking — that stays a separate, deliberate step either way, same
     * as today.
     */
    public static function confirmReservationFeePaid(Booking $booking, int $cashierId, string $paymentMethod, ?string $reference, ?string $paymongoPaymentId = null): void
    {
        DB::transaction(function () use ($booking, $cashierId, $paymentMethod, $reference, $paymongoPaymentId) {
            $booking->update([
                'reservation_fee_status' => 'paid',
                'reservation_fee_paid_at' => now(),
                'approved_by' => $booking->approved_by ?: $cashierId,
            ]);

            Transaction::createWithReceipt([
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'cashier_id' => $cashierId,
                'amount' => $booking->reservation_fee,
                'cash_tendered' => $paymentMethod === 'gcash' ? null : (float) $booking->reservation_fee,
                'change_due' => 0,
                'payment_method' => $paymentMethod,
                'gcash_reference' => $reference,
                'paymongo_payment_id' => $paymongoPaymentId,
                'status' => 'paid',
            ]);
        });
    }

    /**
     * Mark a booking's session balance as paid and record the transaction
     * — mirrors `confirm_balance_payment`'s writes exactly, for the same
     * reason as confirmReservationFeePaid() above.
     */
    public static function confirmBalancePaid(Booking $booking, int $cashierId, string $paymentMethod, ?string $reference, ?string $paymongoPaymentId = null): void
    {
        $amountDue = $booking->remainingBalance();

        DB::transaction(function () use ($booking, $cashierId, $paymentMethod, $reference, $paymongoPaymentId, $amountDue) {
            Transaction::createWithReceipt([
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'cashier_id' => $cashierId,
                'amount' => $amountDue,
                'cash_tendered' => null,
                'change_due' => 0,
                'payment_method' => $paymentMethod,
                'gcash_reference' => $reference,
                'paymongo_payment_id' => $paymongoPaymentId,
                'status' => 'paid',
            ]);

            $booking->update(['payment_status' => 'paid']);
        });
    }
}
