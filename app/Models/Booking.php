<?php

namespace App\Models;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReservationFeeMethod;
use App\Enums\ReservationFeeStatus;
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
    'total_amount', 'reservation_fee', 'reservation_fee_method', 'reservation_fee_reference',
    'reservation_fee_status', 'reservation_fee_paid_at', 'balance_payment_method', 'balance_payment_reference',
    'paymongo_fee_source_id', 'paymongo_balance_source_id',
    'payment_status', 'created_by', 'approved_by', 'approved_at',
    'notes', 'cancel_reason', 'void_admin_reason',
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
            'reservation_fee_method' => ReservationFeeMethod::class,
            'reservation_fee_status' => ReservationFeeStatus::class,
            'balance_payment_method' => ReservationFeeMethod::class,
            'booking_date' => 'date',
            'session_started_at' => 'datetime',
            'session_ends_at' => 'datetime',
            'approved_at' => 'datetime',
            'reservation_fee_paid_at' => 'datetime',
            'total_amount' => 'decimal:2',
            'reservation_fee' => 'decimal:2',
            // Confirmed never queried/sorted/searched (only customer_name
            // is, via staff search) — safe to encrypt with no functional
            // impact.
            'customer_phone' => 'encrypted',
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

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class)->oldest();
    }

    /**
     * A short, unique reference the customer can quote to a cashier (e.g.
     * "I booked BK-000142") — derived straight from the row's own id, so
     * it's guaranteed unique with no extra column, sequence, or race
     * condition to manage.
     */
    public function bookingNo(): string
    {
        return 'BK-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function assetLabel(): string
    {
        return match (true) {
            $this->bookable instanceof Lane => 'Bowling — '.$this->bookable->name,
            $this->bookable instanceof BilliardTable => 'Billiards — '.$this->bookable->name,
            default => 'Asset',
        };
    }

    /**
     * True if a new booking starting at $time (and running for
     * $durationMinutes — null means open-ended/rest of the day, e.g. an
     * Open Time table) would overlap an existing reservation on the same
     * asset/date. This is a real time-range overlap check, not just an
     * exact start_time match — a 2:40 PM/1-hour booking must still block a
     * new booking that starts at 3:00 PM, since the two sessions overlap.
     */
    public static function conflictsAt(string $bookableType, int $bookableId, string $date, string $time, ?int $durationMinutes = null, ?int $excludeId = null): bool
    {
        $startMinutes = self::minutesSinceMidnight($time);
        $endMinutes = $durationMinutes !== null ? $startMinutes + $durationMinutes : PHP_INT_MAX;
        $closeMinutes = \App\Services\AvailabilityService::CLOSE_HOUR * 60;

        return static::query()
            ->where('bookable_type', $bookableType)
            ->where('bookable_id', $bookableId)
            ->whereDate('booking_date', $date)
            ->whereIn('status', ['pending', 'approved', 'occupied'])
            ->when($excludeId, fn ($q) => $q->whereKeyNot($excludeId))
            ->get(['id', 'start_time', 'scoresheets', 'hours'])
            ->contains(function (self $existing) use ($startMinutes, $endMinutes, $closeMinutes) {
                $existingStart = self::minutesSinceMidnight($existing->start_time);
                $existingDuration = match (true) {
                    $existing->scoresheets !== null => (int) $existing->scoresheets * \App\Services\BookingCalculator::MINUTES_PER_SCORESHEET,
                    $existing->hours !== null => (int) $existing->hours * 60,
                    // Open Time table booking — holds the asset for the
                    // rest of the business day.
                    default => $closeMinutes - $existingStart,
                };
                $existingEnd = $existingStart + $existingDuration;

                return $startMinutes < $existingEnd && $endMinutes > $existingStart;
            });
    }

    private static function minutesSinceMidnight(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }

    /**
     * True once the 10% reservation fee is settled (or never applied, e.g.
     * a walk-in session created directly by a cashier) — this is what gates
     * whether a pending online booking can be approved.
     */
    public function reservationFeeSettled(): bool
    {
        return (float) $this->reservation_fee <= 0 || $this->reservation_fee_status === ReservationFeeStatus::Paid;
    }

    /**
     * What's still owed at the counter. Always excludes the reservation fee
     * — it's a mandatory upfront payment on every online booking (10% of
     * the price, or a flat rate for an Open Time table whose price isn't
     * known yet), so it's never part of the session's own bill, even while
     * the cashier hasn't verified it landed yet.
     */
    public function remainingBalance(): float
    {
        return round((float) $this->total_amount - (float) $this->reservation_fee, 2);
    }

    /**
     * True once the customer has sent a GCash reference for the session
     * price but the cashier hasn't verified/confirmed it yet — same idea as
     * the reservation fee's "awaiting_verification" state, just for the
     * balance (payment_status itself only tracks unpaid/paid, with nothing
     * in between).
     */
    public function sessionPaymentAwaitingVerification(): bool
    {
        return $this->payment_status === PaymentStatus::Unpaid
            && $this->balance_payment_method === ReservationFeeMethod::Gcash
            && $this->balance_payment_reference !== null;
    }

    public function sessionPaymentLabel(): string
    {
        return $this->sessionPaymentAwaitingVerification() ? 'Awaiting Verification' : $this->payment_status->label();
    }

    public function sessionPaymentColor(): string
    {
        return $this->sessionPaymentAwaitingVerification() ? 'warning' : $this->payment_status->color();
    }

    public function secondsRemaining(): ?int
    {
        if (! $this->session_ends_at) {
            return null;
        }

        return max(0, now()->diffInSeconds($this->session_ends_at, false));
    }

    /**
     * When this booking's session actually ends — the real, authoritative
     * `session_ends_at` once a session has started, or a scheduled
     * estimate (start_time + scoresheets/hours) before that. Null for an
     * Open Time table booking, whose duration genuinely isn't known until
     * the session is completed.
     */
    public function scheduledEndTime(): ?\Illuminate\Support\Carbon
    {
        if ($this->session_ends_at) {
            return $this->session_ends_at;
        }

        $durationMinutes = match (true) {
            $this->scoresheets !== null => (int) $this->scoresheets * \App\Services\BookingCalculator::MINUTES_PER_SCORESHEET,
            $this->hours !== null => (int) $this->hours * 60,
            default => null,
        };

        if ($durationMinutes === null) {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($this->start_time)->addMinutes($durationMinutes);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status', 'payment_status', 'booking_date', 'start_time', 'end_time',
                'scoresheets', 'hours', 'total_amount', 'reservation_fee',
                'reservation_fee_method', 'reservation_fee_status', 'balance_payment_method',
                'cancel_reason', 'void_admin_reason',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('booking');
    }

}
