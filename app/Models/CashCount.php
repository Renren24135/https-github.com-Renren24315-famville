<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'count_1', 'count_5', 'count_10', 'count_20',
    'count_100', 'count_200', 'count_500', 'count_1000',
    'total_counted', 'expected_amount', 'difference', 'counted_at',
])]
class CashCount extends Model
{
    use HasFactory;

    /**
     * Peso value of each denomination, in the exact order counted.
     */
    public const DENOMINATIONS = [1, 5, 10, 20, 100, 200, 500, 1000];

    protected function casts(): array
    {
        return [
            'total_counted' => 'decimal:2',
            'expected_amount' => 'decimal:2',
            'difference' => 'decimal:2',
            'counted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  array<int, int>  $quantities  denomination value => quantity counted
     */
    public static function totalFor(array $quantities): float
    {
        $total = 0;

        foreach (self::DENOMINATIONS as $denomination) {
            $total += $denomination * (int) ($quantities[$denomination] ?? 0);
        }

        return round($total, 2);
    }
}
