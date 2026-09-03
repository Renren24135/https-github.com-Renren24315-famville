<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['receipt_no', 'booking_id', 'user_id', 'cashier_id', 'amount', 'cash_tendered', 'change_due', 'payment_method', 'status'])]
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

    public static function nextReceiptNo(): string
    {
        return 'FVK-'.now()->format('Ymd').'-'.str_pad((string) (static::whereDate('created_at', now())->count() + 1), 4, '0', STR_PAD_LEFT);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['receipt_no', 'amount', 'cash_tendered', 'change_due', 'payment_method', 'status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('transaction');
    }

}
