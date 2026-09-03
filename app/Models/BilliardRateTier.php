<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['label', 'start_time', 'end_time', 'rate_per_hour', 'is_active'])]
class BilliardRateTier extends Model
{
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'rate_per_hour' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public static function rateForTime(\DateTimeInterface|string $time): ?self
    {
        $time = $time instanceof \DateTimeInterface ? $time->format('H:i:s') : $time;

        return static::query()
            ->where('is_active', true)
            ->where('start_time', '<=', $time)
            ->where('end_time', '>', $time)
            ->first();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['label', 'start_time', 'end_time', 'rate_per_hour', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('billiard_rate_tier');
    }

}
