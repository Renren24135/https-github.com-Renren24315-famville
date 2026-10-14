<?php

namespace App\Services;

use App\Models\BilliardTable;
use App\Models\Booking;
use App\Models\Lane;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Aggregate revenue, utilization, and peak-hour figures for a date range,
     * shared by the executive dashboard and the emailed report.
     */
    public static function summaryFor(Carbon $from, Carbon $to, ?int $cashierId = null): array
    {
        $transactions = Transaction::with(['booking.bookable', 'cashier'])
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($cashierId, fn ($q) => $q->where('cashier_id', $cashierId))
            ->get();

        $dayCount = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay());

        $revenueByDay = collect(range(0, $dayCount))->map(function (int $offset) use ($from, $cashierId) {
            $date = $from->copy()->addDays($offset);

            return [
                'label' => $date->format('M d'),
                'total' => (float) Transaction::whereDate('created_at', $date)
                    ->when($cashierId, fn ($q) => $q->where('cashier_id', $cashierId))
                    ->sum('amount'),
            ];
        });

        $peakHours = Booking::query()
            ->whereIn('status', ['occupied', 'completed'])
            ->whereNotNull('start_time')
            ->whereBetween('booking_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('HOUR(start_time) as hour, COUNT(*) as total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(fn ($row) => ['hour' => (int) $row->hour, 'total' => (int) $row->total]);

        // Split out by asset type too, so the dashboard can chart Bowling vs
        // Billiards separately instead of one blended figure — both series
        // share the same fixed hour range (rather than only hours with any
        // data) so a two-line/grouped-bar chart lines them up correctly.
        $peakHoursByType = function (string $bookableClass) use ($from, $to) {
            $counts = Booking::query()
                ->where('bookable_type', $bookableClass)
                ->whereIn('status', ['occupied', 'completed'])
                ->whereNotNull('start_time')
                ->whereBetween('booking_date', [$from->toDateString(), $to->toDateString()])
                ->selectRaw('HOUR(start_time) as hour, COUNT(*) as total')
                ->groupBy('hour')
                ->pluck('total', 'hour');

            return collect(range(14, 22))->map(fn ($hour) => [
                'hour' => $hour,
                'total' => (int) ($counts[$hour] ?? 0),
            ]);
        };
        $peakHoursLane = $peakHoursByType(Lane::class);
        $peakHoursTable = $peakHoursByType(BilliardTable::class);

        $revenueByDayForType = function (string $bookableClass) use ($from, $dayCount, $cashierId) {
            return collect(range(0, $dayCount))->map(function (int $offset) use ($from, $cashierId, $bookableClass) {
                $date = $from->copy()->addDays($offset);

                return [
                    'label' => $date->format('M d'),
                    'total' => (float) Transaction::whereDate('created_at', $date)
                        ->whereHas('booking', fn ($q) => $q->where('bookable_type', $bookableClass))
                        ->when($cashierId, fn ($q) => $q->where('cashier_id', $cashierId))
                        ->sum('amount'),
                ];
            });
        };
        $revenueByDayLane = $revenueByDayForType(Lane::class);
        $revenueByDayTable = $revenueByDayForType(BilliardTable::class);

        $laneUtilization = Lane::withCount(['bookings as completed_count' => function ($q) use ($from, $to) {
            $q->whereIn('status', ['occupied', 'completed'])
                ->whereBetween('booking_date', [$from->toDateString(), $to->toDateString()]);
        }])->get();

        $tableUtilization = BilliardTable::withCount(['bookings as completed_count' => function ($q) use ($from, $to) {
            $q->whereIn('status', ['occupied', 'completed'])
                ->whereBetween('booking_date', [$from->toDateString(), $to->toDateString()]);
        }])->get();

        return [
            'from' => $from,
            'to' => $to,
            'totalRevenue' => (float) $transactions->sum('amount'),
            // A booking paid in two parts (reservation fee + session
            // balance) is one sale, not two — group by booking so it's
            // only counted once, matching Staff\TransactionController's
            // listing. Transactions with no booking (shouldn't normally
            // happen, but defensively) each still count individually.
            'transactionCount' => $transactions->groupBy(fn ($t) => $t->booking_id ?? 'standalone-'.$t->id)->count(),
            'pendingCount' => Booking::where('status', 'pending')->count(),
            'occupiedCount' => Lane::where('status', 'occupied')->count() + BilliardTable::where('status', 'occupied')->count(),
            'totalAssets' => Lane::count() + BilliardTable::count(),
            'revenueByDay' => $revenueByDay,
            'revenueByDayLane' => $revenueByDayLane,
            'revenueByDayTable' => $revenueByDayTable,
            'peakHours' => $peakHours,
            'peakHoursLane' => $peakHoursLane,
            'peakHoursTable' => $peakHoursTable,
            'laneUtilization' => $laneUtilization,
            'tableUtilization' => $tableUtilization,
            'transactions' => $transactions,
        ];
    }

    public static function todaySummary(?int $cashierId = null): array
    {
        $today = Carbon::today();

        return self::summaryFor($today, $today, $cashierId);
    }

    public static function forRange(string $range, ?int $cashierId = null): array
    {
        $to = Carbon::yesterday()->endOfDay();

        return match ($range) {
            'weekly' => self::summaryFor(Carbon::yesterday()->subDays(6)->startOfDay(), $to, $cashierId),
            default => self::summaryFor(Carbon::yesterday()->startOfDay(), $to, $cashierId),
        };
    }

    /**
     * @return Collection<int, string>
     */
    public static function recipientEmails(): Collection
    {
        $raw = \App\Models\Setting::get('report_recipients');

        if (! $raw) {
            return \App\Models\User::role('admin')->pluck('email');
        }

        return collect(explode(',', $raw))
            ->map(fn ($email) => trim($email))
            ->filter();
    }
}
