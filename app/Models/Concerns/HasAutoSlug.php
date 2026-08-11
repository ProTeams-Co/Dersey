<?php

namespace App\Models\Concerns;

use App\Support\Slug;

/**
 * For {model}_translations rows: fills `slug` from the translation's own
 * `name` (or another column via slugSourceColumn()) when one isn't given,
 * unique per (table, locale) - a slug can repeat across locales (a
 * different language's translation of the same or a different record) but
 * never within one, per this batch's UNIQUE(slug, locale) constraint.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 * @method static void saving(callable $callback)
 */
trait HasAutoSlug
{
    protected static function bootHasAutoSlug(): void
    {
        static::saving(function ($translation) {
            if ($translation->slug) {
                return;
            }

            $source = $translation->{$translation->slugSourceColumn()};

            if (! $source) {
                return;
            }

            $base = Slug::generate($source, $translation->locale);

            $translation->slug = Slug::unique($base, function (string $candidate) use ($translation) {
                return $translation->newQuery()
                    ->where('locale', $translation->locale)
                    ->where('slug', $candidate)
                    ->when(
                        $translation->exists,
                        fn ($query) => $query->where($translation->getKeyName(), '!=', $translation->getKey())
                    )
                    ->exists();
            });
        });
    }

    protected function slugSourceColumn(): string
    {
        return 'name';
    }
}
