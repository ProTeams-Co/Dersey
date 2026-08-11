<?php

namespace App\Support\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The separate-table half of the project's hybrid translation approach
 * (JSON columns, via spatie/laravel-translatable, cover everything else —
 * see CLAUDE.md). A model using this trait must declare which of its own
 * attributes are translated:
 *
 *   protected array $translatable = ['name', 'description'];
 *
 * and have a matching {Model}Translation model (override translationModel()
 * if it doesn't follow that naming convention) extending App\Models\
 * Translation, with a standard {model}_translations table:
 * id, {model}_id (FK cascade), locale, <translated columns>,
 * UNIQUE({model}_id, locale), INDEX(locale).
 */
trait HasTranslations
{
    public function translations(): HasMany
    {
        return $this->hasMany($this->translationModel());
    }

    /**
     * Override on the model if its translation class doesn't follow the
     * {Model}Translation naming convention (e.g. Product -> ProductTranslation).
     */
    public function translationModel(): string
    {
        return static::class.'Translation';
    }

    /**
     * The translation row for $locale, falling back to the app's default
     * locale if this model was never translated into $locale at all.
     * Reads from the already-loaded `translations` relation only — see
     * scopeWithCurrentTranslation() below for why nothing here triggers a
     * lazy load itself.
     */
    public function translate(?string $locale = null): ?Model
    {
        $locale ??= app()->getLocale();
        $fallback = config('app.fallback_locale');

        return $this->translations->firstWhere('locale', $locale)
            ?? $this->translations->firstWhere('locale', $fallback);
    }

    /**
     * Filter the parent query by a value on one of its translated columns,
     * in a specific locale (current locale if omitted). No fallback here —
     * this is a search/filter operation, not a display one, so it must
     * match the real stored value for that exact locale.
     */
    public function scopeWhereTranslation(Builder $query, string $field, string $value, ?string $locale = null): Builder
    {
        $locale ??= app()->getLocale();

        return $query->whereHas('translations', function (Builder $q) use ($field, $value, $locale) {
            $q->where('locale', $locale)->where($field, $value);
        });
    }

    /**
     * Eager-loads only the current locale's translation row (plus the
     * fallback locale's, in case the current one is missing) instead of
     * every locale the model happens to have — the single biggest N+1 /
     * over-fetch risk with this pattern. Any code path that reads a
     * translatable attribute on more than one model at a time must go
     * through this scope first; preventLazyLoading (enabled outside
     * production) throws instead of silently N+1-ing if it doesn't.
     */
    public function scopeWithCurrentTranslation(Builder $query, ?string $locale = null): Builder
    {
        $locale ??= app()->getLocale();
        $fallback = config('app.fallback_locale');
        $locales = array_unique([$locale, $fallback]);

        return $query->with(['translations' => function (Builder|HasMany $q) use ($locales) {
            $q->whereIn('locale', $locales);
        }]);
    }

    /**
     * Lets `$model->name` resolve straight to the current-locale
     * translation without every caller writing `$model->translate()->name`
     * by hand. Only intercepts keys the model itself declared translatable;
     * everything else behaves exactly like normal Eloquent.
     */
    public function getAttribute($key)
    {
        if (in_array($key, $this->translatable ?? [], true)) {
            return $this->translate()?->getAttribute($key);
        }

        return parent::getAttribute($key);
    }
}
