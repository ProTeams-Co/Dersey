<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Every translatable resource (categories, brands, ...) enforces
 * UNIQUE(slug, locale) at the database level (Batch 2.2) - this is the
 * admin-form-side counterpart, checked before save so a duplicate slug
 * fails with a clear "this slug is taken in Arabic/English" message
 * instead of a raw QueryException. $ignoreModelId excludes the row being
 * edited itself (by the model's own id via $foreignKey, not the
 * translation row's id - a model keeping its own current slug on update
 * must not fail against itself).
 */
class UniqueSlugPerLocale implements ValidationRule
{
    public function __construct(
        private readonly string $table,
        private readonly string $foreignKey,
        private readonly string $locale,
        private readonly string $localeLabel,
        private readonly ?int $ignoreModelId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = DB::table($this->table)
            ->where('locale', $this->locale)
            ->where('slug', $value);

        if ($this->ignoreModelId !== null) {
            $query->where($this->foreignKey, '!=', $this->ignoreModelId);
        }

        if ($query->exists()) {
            $fail(__('admin.form.slug_taken_in_locale', ['locale' => $this->localeLabel]));
        }
    }
}
