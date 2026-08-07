<?php

namespace App\Support\Cache;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 * @phpstan-require-extends Model
 */
trait HasVersionedCache
{
    protected static function bootHasVersionedCache(): void
    {
        static::saved(fn (Model $model) => VersionedCache::bump($model->cacheTag()));
        static::deleted(fn (Model $model) => VersionedCache::bump($model->cacheTag()));
    }

    /**
     * @param  \Illuminate\Events\QueuedClosure|callable|array|class-string  $callback
     */
    abstract public static function saved($callback);

    /**
     * @param  \Illuminate\Events\QueuedClosure|callable|array|class-string  $callback
     */
    abstract public static function deleted($callback);

    /**
     * Override on the model if it should share a tag with another model,
     * or use a name different from its class (e.g. "category").
     */
    public function cacheTag(): string
    {
        return Str::snake(class_basename(static::class));
    }
}
