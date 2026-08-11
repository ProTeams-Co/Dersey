<?php

namespace App\Models;

class BannerTranslation extends Translation
{
    protected $fillable = [
        'banner_id',
        'locale',
        'title',
        'subtitle',
        'button_text',
        'alt',
    ];
}
