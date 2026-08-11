<?php

namespace App\Models;

use App\Models\Concerns\HasDefaultActivityLog;
use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * Every regular model in the app extends this instead of Eloquent's own
 * Model directly, so activity logging (and anything else added here later)
 * is automatic rather than something each model has to remember to opt
 * into. User and Admin are the exception — they extend Laravel's own
 * Authenticatable base class instead (required for auth) and apply
 * HasDefaultActivityLog directly; see that trait.
 *
 * preventLazyLoading()/preventSilentlyDiscardingAttributes() are global
 * static toggles already set in AppServiceProvider (from Batch 1.1) — they
 * apply regardless of which base class a model extends, so they are not
 * repeated here.
 */
abstract class Model extends EloquentModel
{
    use HasDefaultActivityLog;
}
