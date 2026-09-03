<?php

namespace App\Models;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'bookable_type', 'bookable_id', 'user_id', 'customer_name', 'customer_phone',
    'source', 'status', 'booking_date', 'start_time', 'end_time',
    'scoresheets', 'hours', 'session_started_at', 'session_ends_at',
    'total_amount', 'payment_status', 'created_by', 'approved_by', 'approved_at',
    'notes', 'cancel_reason',
])]
class Booking extends Model
{
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'source' => BookingSource::class,
            'status' => BookingStatus::class,
            'payment_status' => PaymentStatus::class,
            'booking_date' => 'date',
            'session_started_at' => 'datetime',
            'session_ends_at' => 'datetime',
            'approved_at' => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }

    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }

    public function assetLabel(): string
    {
        return match (true) {
            $this->bookable instanceof Lane => 'Bowling — '.$this->bookable->name,
            $this->bookable instanceof BilliardTable => 'Billiards — '.$this->bookable->name,
            default => 'Asset',
        };
    }

    public function secondsRemaining(): ?int
    {
        if (! $this->session_ends_at) {
            return null;
        }

        return max(0, now()->diffInSeconds($this->session_ends_at, false));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status', 'payment_status', 'booking_date', 'start_time', 'end_time',
                'scoresheets', 'hours', 'total_amount',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('booking');
    }

}
