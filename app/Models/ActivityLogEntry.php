<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity;

class ActivityLogEntry extends Activity
{
    protected static function booted(): void
    {
        static::creating(function (Activity $activity) {
            $activity->properties = collect($activity->properties ?? [])->merge([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }
}
