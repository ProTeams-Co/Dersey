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

    /**
     * logFillable() is required, not cosmetic - LogOptions::defaults()'s
     * own $logAttributes stays empty otherwise (logOnlyDirty() only
     * decides whether to log *changes*, not *which* attributes are even
     * candidates), so every automatic log before this fix was silently a
     * no-op: attributesToBeLogged() returned [] every single time, and
     * dontSubmitEmptyLogs() then discarded the empty result before it
     * ever reached the database - a real gap in this trait discovered
     * while verifying Batch 3.0's "activitylog على كل تعديل" requirement,
     * not specific to the admin panel. Fillable attributes are the
     * correct set to watch: the same fields mass-assignment already
     * allows changing, which by construction excludes hidden/system-
     * computed columns no model exposes in $fillable.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName(class_basename(static::class));
    }
}
