<?php

namespace App\Models;

use App\Enums\AssetStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['name', 'status', 'notes'])]
class BilliardTable extends Model
{
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
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

    /**
     * See Lane::orderedByName() — same reasoning, kept consistent across
     * both asset types.
     */
    public static function orderedByName(): \Illuminate\Support\Collection
    {
        return static::query()->get()->sortBy('name', SORT_NATURAL)->values();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status', 'notes'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('billiard_table');
    }

}
