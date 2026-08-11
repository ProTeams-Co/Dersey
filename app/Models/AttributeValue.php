<?php

namespace App\Models;

use App\Support\Traits\HasTranslations;
use Database\Factories\AttributeValueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttributeValue extends Model
{
    /** @use HasFactory<AttributeValueFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

    protected $fillable = [
        'attribute_id',
        'color_hex',
        'image',
        'sort',
    ];

    protected array $translatable = ['value'];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
        ];
    }

    public function translationModel(): string
    {
        return AttributeValueTranslation::class;
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
