<?php

namespace App\Models;

use App\Enums\AssetStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['name', 'price_per_scoresheet', 'status', 'notes'])]
class Lane extends Model
{
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'price_per_scoresheet' => 'decimal:2',
            'status' => AssetStatus::class,
        ];
    }

    public function bookings(): MorphMany
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    public function activeBooking(): ?Booking
    {
        return $this->bookings()
            ->whereIn('status', ['approved', 'occupied'])
            ->whereDate('booking_date', now()->toDateString())
            ->latest('session_started_at')
            ->first();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'price_per_scoresheet', 'status', 'notes'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('lane');
    }

}
