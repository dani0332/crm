<?php

namespace App\Services\Cache;

use App\Enums\CacheKeyEnum;
use Illuminate\Support\Facades\Cache;

class CacheManager
{
    public static function remember(CacheKeyEnum $key, callable $callback)
    {
        return Cache::remember($key->value, $key->expiry(), $callback);
    }

    public static function forget(CacheKeyEnum $key)
    {
        Cache::forget($key->value);
    }
}
