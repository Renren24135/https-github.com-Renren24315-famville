<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A permanent snapshot of one calendar month's revenue, saved once that
 * month closes — Payment History's "This Month" figures reset to zero at
 * the start of the next month, but the totals aren't lost: they're already
 * sitting here.
 */
#[Fillable(['month', 'total_transactions', 'total_revenue', 'lane_revenue', 'table_revenue', 'archived_at'])]
class MonthlyRevenueArchive extends Model
{
    protected function casts(): array
    {
        return [
            'month' => 'date',
            'total_revenue' => 'decimal:2',
            'lane_revenue' => 'decimal:2',
            'table_revenue' => 'decimal:2',
            'archived_at' => 'datetime',
        ];
    }
}
