<?php

namespace App\Observers\Concerns;

use App\Enums\RedirectStatusCode;
use App\Models\Redirect;
use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared by CategoryTranslationObserver/ProductTranslationObserver: when a
 * translated slug changes, auto-create a redirect old path -> new path so a
 * rename doesn't silently break an already-indexed/shared link.
 *
 * Deliberately does not build the path itself - $pathBuilder is always the
 * owning model's own seoPath(slug, locale) static method (Product::seoPath()/
 * Category::seoPath()), so the URL convention lives in exactly one place per
 * model. See those methods' TODO(4.x) docblocks for why the convention is
 * still a placeholder.
 */
trait GeneratesSlugRedirect
{
    protected function handleSlugChange(Model $translation, Closure $pathBuilder): void
    {
        if ($translation->wasRecentlyCreated || ! $translation->wasChanged('slug')) {
            return;
        }

        $oldSlug = $translation->getOriginal('slug');
        $newSlug = $translation->slug;

        if (! $oldSlug || $oldSlug === $newSlug) {
            return;
        }

        $locale = $translation->locale;

        Redirect::query()->updateOrCreate(
            ['from_path' => $pathBuilder($oldSlug, $locale)],
            [
                'to_path' => $pathBuilder($newSlug, $locale),
                'status_code' => RedirectStatusCode::Permanent,
                'is_active' => true,
            ]
        );
    }
}
