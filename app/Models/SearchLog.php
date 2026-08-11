<?php

namespace App\Models;

class SearchLog extends Model
{
    protected $fillable = [
        'term',
        'normalized_term',
        'results_count',
        'user_id',
        'session_id',
        'locale',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'results_count' => 'integer',
        ];
    }
}
