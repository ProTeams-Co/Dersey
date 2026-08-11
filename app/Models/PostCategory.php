<?php

namespace App\Models;

use App\Support\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostCategory extends Model
{
    use HasTranslations;

    protected $fillable = ['sort'];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
        ];
    }

    protected array $translatable = ['name', 'slug'];

    public function translationModel(): string
    {
        return PostCategoryTranslation::class;
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
