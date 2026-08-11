<?php

namespace App\Support\Seo;

/**
 * The result of HasSeo::seoMeta() - a custom SeoMeta row's values merged
 * over the owning model's own computed defaults, field by field (a custom
 * row that only sets `title` still falls back to the model's default
 * description/image/etc., rather than an all-or-nothing override).
 */
final readonly class SeoMetaData
{
    public function __construct(
        public string $title,
        public ?string $description,
        public ?string $keywords,
        public string $ogTitle,
        public ?string $ogDescription,
        public ?string $ogImage,
        public ?string $canonicalUrl,
        public string $robots = 'index, follow',
    ) {}
}
