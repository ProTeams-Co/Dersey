<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * The activity-log configuration every model shares, factored out of
 * App\Models\Model so it can also be applied directly to User/Admin —
 * those two extend Laravel's own Authenticatable base class instead (auth
 * needs it), which PHP's single inheritance means they can't also extend
 * App\Models\Model. Same logging behavior either way.
 */
trait HasDefaultActivityLog
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName(class_basename(static::class));
    }
}
