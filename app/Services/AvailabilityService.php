<?php

namespace App\Services;

use App\Models\AssetClosure;
use App\Models\BilliardTable;
use App\Models\Booking;
use App\Models\Lane;
use Carbon\Carbon;

class AvailabilityService
{
    public const OPEN_HOUR = 14;

    public const CLOSE_HOUR = 22;

    /**
     * Build the timetable for one lane/table on one date, so customers can
     * see at a glance which slots are already reserved before they pick a
     * start time. Bookings can start at any minute (e.g. 2:43 PM), not just
     * on the hour, so this works off exact start/end times rather than
     * fixed hourly buckets — a 2:43 PM booking blocks exactly 2:43–3:43 PM,
     * and the next free block starts right at 3:43 PM.
     */
    public static function timetableFor(Lane|BilliardTable $asset, string $date): array
    {
        $openMinutes = self::OPEN_HOUR * 60;
        $closeMinutes = self::CLOSE_HOUR * 60;

        // A closed date pre-empts the whole day — no point computing
        // booking-based segments for a day nobody can book anyway, and
        // this is what makes the closure visible on the timetable instead
        // of only rejecting at submit.
        if ($closure = AssetClosure::closing($asset, $date)) {
            return [[
                'start_time' => self::minutesToTime($openMinutes),
                'end_time' => self::minutesToTime($closeMinutes),
                'label' => 'All Day — Closed: '.$closure->reason,
                'status' => 'closed',
            ]];
        }

        $bookings = $asset->bookings()
            ->whereDate('booking_date', $date)
            ->whereIn('status', ['pending', 'approved', 'occupied'])
            ->get();

        $ranges = $bookings->map(function (Booking $booking) use ($openMinutes, $closeMinutes) {
            $start = Carbon::parse($booking->start_time)->format('H:i');
            $startMinutes = self::minutesSinceMidnight($start);

            $durationMinutes = match (true) {
                $booking->scoresheets !== null => (int) $booking->scoresheets * BookingCalculator::MINUTES_PER_SCORESHEET,
                $booking->hours !== null => (int) $booking->hours * 60,
                // Open Time table bookings have no fixed duration — the
                // table is held for the rest of the business day.
                default => $closeMinutes - $startMinutes,
            };

            return [
                'id' => $booking->id,
                'start' => max($openMinutes, $startMinutes),
                'end' => min($closeMinutes, $startMinutes + $durationMinutes),
                // The cashier has actually started the session, vs. it just
                // being held pending/approved — shown differently so
                // customers can tell "reserved" apart from "in use now".
                'in_use' => $booking->status->value === 'occupied',
                // Pending = submitted but not yet approved by a cashier. It
                // still holds the slot, but reads differently from a
                // confirmed reservation so nobody mistakes it for approved.
                'pending' => $booking->status->value === 'pending',
            ];
        })->filter(fn (array $range) => $range['end'] > $range['start']);

        // A free slot that's already elapsed today (e.g. it's 3:34 PM and
        // the slot ran 2:00–3:22 PM) isn't bookable — it reads as "past",
        // not "free". Only applies to today; every slot on a future date is
        // still genuinely open. Clamped so "now" never lands outside the
        // business day (nothing to split before opening or after closing).
        $nowMinutes = $date === now()->toDateString()
            ? self::minutesSinceMidnight(now()->format('H:i'))
            : null;
        $splitAtNow = $nowMinutes !== null && $nowMinutes > $openMinutes && $nowMinutes < $closeMinutes;

        // Every distinct start/end minute is a point where the status could
        // change — walking through them in order gives exact-minute segments
        // instead of coarse hourly ones.
        $boundaries = $ranges->flatMap(fn (array $range) => [$range['start'], $range['end']])
            ->push($openMinutes)
            ->push($closeMinutes)
            ->when($splitAtNow, fn ($b) => $b->push($nowMinutes))
            ->unique()
            ->sort()
            ->values();

        $segments = [];

        for ($i = 0; $i < $boundaries->count() - 1; $i++) {
            $segmentStart = $boundaries[$i];
            $segmentEnd = $boundaries[$i + 1];

            $overlapping = $ranges->filter(fn (array $range) => $segmentStart < $range['end'] && $segmentEnd > $range['start']);
            $isPast = $nowMinutes !== null && $segmentEnd <= $nowMinutes;

            $segments[] = [
                'start' => $segmentStart,
                'end' => $segmentEnd,
                'status' => match (true) {
                    $overlapping->isEmpty() && $isPast => 'past',
                    $overlapping->isEmpty() => 'free',
                    $overlapping->contains('in_use', true) => 'in_use',
                    $overlapping->contains('pending', false) => 'reserved',
                    default => 'pending',
                },
                // Which actual booking(s) this segment belongs to — null
                // for a genuinely free segment. Two touching segments only
                // ever get merged into one visual block when they share
                // this, so two separate reservations that happen to sit
                // back-to-back stay visually distinct instead of reading
                // as one long booking.
                'group' => $overlapping->isEmpty() ? null : $overlapping->pluck('id')->sort()->values()->implode(','),
            ];
        }

        return self::mergeIntoBlocks(self::dropTooShortToBook($segments));
    }

    /**
     * A minimum booking is 1 hour, so a "free" gap shorter than that isn't
     * actually bookable — e.g. one reservation ending 2:40–3:40 PM and the
     * next starting 4:00 PM leaves a 20-minute gap nobody can book. Simply
     * leave it off the list rather than stretching a real reservation's
     * displayed time to cover it — a booking that actually started 2:16 PM
     * must still read "2:16 PM – 3:16 PM", never padded out to "2:00 PM"
     * just because the 16 minutes before it aren't independently bookable.
     */
    private static function dropTooShortToBook(array $segments): array
    {
        return array_values(array_filter(
            $segments,
            fn (array $segment) => $segment['status'] !== 'free' || $segment['end'] - $segment['start'] >= 60
        ));
    }

    /**
     * Collapse consecutive segments that share a status AND belong to the
     * same reservation(s) into a single block, so one booking's span reads
     * as one row instead of several. Two different reservations never
     * merge even when they're touching or share the same status.
     */
    private static function mergeIntoBlocks(array $segments): array
    {
        $blocks = [];

        foreach ($segments as $segment) {
            $last = count($blocks) - 1;

            if ($last >= 0
                && $blocks[$last]['status'] === $segment['status']
                && $blocks[$last]['end'] === $segment['start']
                && $blocks[$last]['group'] === $segment['group']) {
                $blocks[$last]['end'] = $segment['end'];

                continue;
            }

            $blocks[] = $segment;
        }

        return array_map(fn (array $block) => self::block($block['start'], $block['end'], $block['status']), $blocks);
    }

    private static function block(int $startMinutes, int $endMinutes, string $status): array
    {
        return [
            'start_time' => self::minutesToTime($startMinutes),
            'end_time' => self::minutesToTime($endMinutes),
            'label' => self::minutesToLabel($startMinutes).' – '.self::minutesToLabel($endMinutes),
            'status' => $status,
        ];
    }

    private static function minutesSinceMidnight(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }

    private static function minutesToTime(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private static function minutesToLabel(int $minutes): string
    {
        return Carbon::createFromTime(intdiv($minutes, 60), $minutes % 60)->format('g:i A');
    }
}
