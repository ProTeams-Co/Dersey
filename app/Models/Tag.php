<?php

namespace App\Models;

use App\Support\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    use HasTranslations;

    protected array $translatable = ['name', 'slug'];

    public function translationModel(): string
    {
        return TagTranslation::class;
    }

    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }
}
