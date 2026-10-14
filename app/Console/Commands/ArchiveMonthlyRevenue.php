<?php

namespace App\Console\Commands;

use App\Models\BilliardTable;
use App\Models\Lane;
use App\Models\MonthlyRevenueArchive;
use App\Models\Transaction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('app:archive-monthly-revenue {--month= : The month to archive, as YYYY-MM. Defaults to last month.}')]
#[Description('Snapshot a calendar month\'s transaction totals into monthly_revenue_archives, so Payment History\'s "This Month" figures can safely reset to zero without losing history.')]
class ArchiveMonthlyRevenue extends Command
{
    public function handle(): int
    {
        $month = $this->option('month')
            ? Carbon::createFromFormat('Y-m', $this->option('month'))->startOfMonth()
            : now()->subMonthNoOverflow()->startOfMonth();

        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $transactions = Transaction::with('booking')
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $laneRevenue = $transactions
            ->filter(fn (Transaction $t) => $t->booking?->bookable_type === Lane::class)
            ->sum('amount');

        $tableRevenue = $transactions
            ->filter(fn (Transaction $t) => $t->booking?->bookable_type === BilliardTable::class)
            ->sum('amount');

        MonthlyRevenueArchive::updateOrCreate(
            ['month' => $from->toDateString()],
            [
                'total_transactions' => $transactions->count(),
                'total_revenue' => $transactions->sum('amount'),
                'lane_revenue' => $laneRevenue,
                'table_revenue' => $tableRevenue,
                'archived_at' => now(),
            ]
        );

        $this->info("Archived {$from->format('F Y')}: {$transactions->count()} transactions, ₱".number_format($transactions->sum('amount'), 2).' collected.');

        return self::SUCCESS;
    }
}
