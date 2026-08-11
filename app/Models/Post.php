<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Support\Traits\HasSeo;
use App\Support\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasSeo, HasTranslations, SoftDeletes;

    protected $fillable = [
        'post_category_id',
        'author_id',
        'featured_image',
        'status',
        'published_at',
        'views_count',
        'reading_time',
        'is_featured',
    ];

    protected array $translatable = ['title', 'slug', 'excerpt', 'content', 'meta_title', 'meta_description'];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
            'views_count' => 'integer',
            'reading_time' => 'integer',
            'is_featured' => 'boolean',
        ];
    }

    public function translationModel(): string
    {
        return PostTranslation::class;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'author_id');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Published)
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function defaultSeoTitle(?string $locale = null): string
    {
        return $this->translate($locale)?->title ?? '';
    }

    public function defaultSeoDescription(?string $locale = null): ?string
    {
        $translation = $this->translate($locale);
        $text = $translation?->excerpt ?: $translation?->content;

        return $text ? Str::limit(strip_tags($text), 160) : null;
    }

    public function defaultSeoImage(): ?string
    {
        return $this->featured_image;
    }

    /**
     * @return array<string, mixed>
     */
    public function schemaMarkup(?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $translation = $this->translate($locale);

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $translation?->title,
            'description' => $translation?->excerpt,
            'image' => $this->featured_image,
            'datePublished' => $this->published_at?->toIso8601String(),
            'dateModified' => $this->updated_at?->toIso8601String(),
            'author' => $this->author ? [
                '@type' => 'Person',
                'name' => $this->author->name,
            ] : null,
        ];
    }
}
