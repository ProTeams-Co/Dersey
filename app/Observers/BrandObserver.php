<?php

namespace App\Observers;

use App\Models\Brand;
use App\Support\Cache\VersionedCache;

class BrandObserver
{
    public function saved(Brand $brand): void
    {
        VersionedCache::bump('brand');
    }

    public function deleted(Brand $brand): void
    {
        VersionedCache::bump('brand');
    }
}
