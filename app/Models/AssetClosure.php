<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['bookable_type', 'bookable_id', 'start_date', 'end_date', 'reason', 'created_by'])]
class AssetClosure extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Closures matching the given asset — a whole-category closure
     * (`bookable_id` null) always applies, alongside any closure targeted
     * at this exact asset.
     */
    public function scopeFor(Builder $query, string $bookableType, ?int $bookableId): Builder
    {
        return $query->where('bookable_type', $bookableType)
            ->where(function (Builder $q) use ($bookableId) {
                $q->whereNull('bookable_id');

                if ($bookableId) {
                    $q->orWhere('bookable_id', $bookableId);
                }
            });
    }

    public function isLane(): bool
    {
        return $this->bookable_type === Lane::class;
    }

    /**
     * "All Bowling Lanes" for a whole-category closure, or the specific
     * asset's own name when this closure targets just one lane/table.
     */
    public function assetScopeLabel(): string
    {
        if (! $this->bookable_id) {
            return $this->isLane() ? 'All Bowling Lanes' : 'All Billiard Tables';
        }

        $asset = $this->isLane() ? Lane::find($this->bookable_id) : BilliardTable::find($this->bookable_id);

        return $asset?->name ?? '(deleted asset)';
    }

    /**
     * The single lookup point used everywhere this feature needs to ask
     * "is this lane/table closed on this date?" — booking creation, the
     * walk-in form, and the availability timetable all call this.
     */
    public static function closing(Lane|BilliardTable $asset, string $date): ?self
    {
        return static::query()
            ->for($asset::class, $asset->id)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();
    }
}
