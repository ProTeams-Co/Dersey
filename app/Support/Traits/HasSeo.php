<?php

namespace App\Support\Traits;

use App\Models\SeoMeta;
use App\Support\Seo\SeoMetaData;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A model using this trait must implement defaultSeoTitle()/
 * defaultSeoDescription()/defaultSeoImage() (declared abstract below) so
 * seoMeta() always has something sensible to fall back to when no custom
 * SeoMeta row exists for the requested locale, and may override
 * schemaMarkup() (defaults to [] here) to emit its own JSON-LD type.
 *
 * seoMetas() is a MorphMany, not MorphOne - seo_metas has
 * UNIQUE(seoable_type, seoable_id, locale), i.e. one row per *locale*, not
 * one row per model.
 *
 * seoMeta() queries directly (seoMetas()->where(...)->first()) rather than
 * reading the already-loaded seoMetas collection, so it never depends on
 * the caller having eager-loaded anything first - unlike translations,
 * there is no batch-display use case here that would make preloading worth
 * the extra ceremony.
 */
trait HasSeo
{
    public function seoMetas(): MorphMany
    {
        return $this->morphMany(SeoMeta::class, 'seoable');
    }

    public function seoMeta(?string $locale = null): SeoMetaData
    {
        $locale ??= app()->getLocale();

        $custom = $this->seoMetas()->where('locale', $locale)->first();

        $title = $custom?->title ?? $this->defaultSeoTitle($locale);
        $description = $custom?->description ?? $this->defaultSeoDescription($locale);

        return new SeoMetaData(
            title: $title,
            description: $description,
            keywords: $custom?->keywords,
            ogTitle: $custom?->og_title ?? $title,
            ogDescription: $custom?->og_description ?? $description,
            ogImage: $custom?->og_image ?? $this->defaultSeoImage(),
            canonicalUrl: $custom?->canonical_url,
            robots: $custom?->robots ?? 'index, follow',
        );
    }

    /**
     * JSON-LD structured data as a plain array - each overriding model
     * includes its own "@context"/"@type" keys. Empty by default: not
     * every seoable model needs schema markup (e.g. a Page might not).
     *
     * @return array<string, mixed>
     */
    public function schemaMarkup(?string $locale = null): array
    {
        return [];
    }

    abstract public function defaultSeoTitle(?string $locale = null): string;

    abstract public function defaultSeoDescription(?string $locale = null): ?string;

    abstract public function defaultSeoImage(): ?string;
}
