<?php

namespace App\Services;

use App\Models\BilliardRateTier;
use App\Models\BilliardTable;
use App\Models\Booking;
use App\Models\Lane;
use Carbon\Carbon;

class BookingCalculator
{
    /**
     * Assumed playtime per bowling scoresheet, used to estimate the
     * countdown shown on the lane's occupied timer.
     */
    public const MINUTES_PER_SCORESHEET = 30;

    public static function amountFor(Lane|BilliardTable $asset, ?int $scoresheets, ?int $hours, string $startTime): float
    {
        if ($asset instanceof Lane) {
            return round((float) $asset->price_per_scoresheet * (int) $scoresheets, 2);
        }

        $tier = BilliardRateTier::rateForTime($startTime);
        $rate = $tier?->rate_per_hour ?? 0;

        return round((float) $rate * (int) $hours, 2);
    }

    public static function sessionMinutes(Lane|BilliardTable $asset, ?int $scoresheets, ?int $hours): int
    {
        if ($asset instanceof Lane) {
            return (int) $scoresheets * self::MINUTES_PER_SCORESHEET;
        }

        return (int) $hours * 60;
    }

    /**
     * Start the live session on a booking: stamps session_started_at/ends_at,
     * computes the bill, and flips the asset + booking to occupied.
     */
    public static function startSession(Booking $booking): void
    {
        $asset = $booking->bookable;
        $minutes = self::sessionMinutes($asset, $booking->scoresheets, $booking->hours);
        $startedAt = Carbon::now();

        $booking->update([
            'status' => 'occupied',
            'session_started_at' => $startedAt,
            'session_ends_at' => $startedAt->copy()->addMinutes($minutes),
            'total_amount' => self::amountFor($asset, $booking->scoresheets, $booking->hours, $startedAt->format('H:i:s')),
        ]);

        $asset->update(['status' => 'occupied']);
    }
}
