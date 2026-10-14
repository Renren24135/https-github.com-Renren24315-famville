<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\UniqueConstraintViolationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['receipt_no', 'booking_id', 'user_id', 'cashier_id', 'amount', 'cash_tendered', 'change_due', 'payment_method', 'gcash_reference', 'paymongo_payment_id', 'status'])]
class Transaction extends Model
{
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'cash_tendered' => 'decimal:2',
            'change_due' => 'decimal:2',
            'status' => PaymentStatus::class,
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * Builds today's next receipt number from the highest suffix actually in
     * use, not a row count — a plain count silently reissues an already-used
     * number once any of today's transactions have been deleted (e.g. test
     * data cleanup), since the count drops below the highest number already
     * taken and collides with it.
     */
    public static function nextReceiptNo(): string
    {
        $prefix = 'FVK-'.now()->format('Ymd').'-';

        $lastSuffix = static::where('receipt_no', 'like', $prefix.'%')
            ->pluck('receipt_no')
            ->map(fn (string $receiptNo) => (int) substr($receiptNo, strlen($prefix)))
            ->max();

        return $prefix.str_pad((string) (($lastSuffix ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a transaction with a fresh receipt number, retrying once if two
     * cashiers finish a payment at the exact same instant and both compute
     * the same "next" number before either row exists yet.
     */
    public static function createWithReceipt(array $attributes): self
    {
        $attempts = 0;

        do {
            try {
                return static::create(['receipt_no' => static::nextReceiptNo(), ...$attributes]);
            } catch (UniqueConstraintViolationException $e) {
                if (++$attempts >= 3) {
                    throw $e;
                }
            }
        } while (true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['receipt_no', 'amount', 'cash_tendered', 'change_due', 'payment_method', 'gcash_reference', 'status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('transaction');
    }

}
