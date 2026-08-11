<?php

namespace App\Models;

class FaqTranslation extends Translation
{
    protected $fillable = [
        'faq_id',
        'locale',
        'question',
        'answer',
    ];
}
