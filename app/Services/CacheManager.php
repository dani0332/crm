<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheManager
{
    public static function setWithExpiration($key, $value, $minutes)
    {
        Cache::put($key, $value, $minutes);
    }

    public static function setWithTag($key, $value, $tag, $endTime)
    {
        Cache::rememberForever($key, function () use ($value) {
            return $value;
        })->tag([$tag => $endTime]);
    }

    public static function get($key)
    {
        return Cache::get($key);
    }

    public static function forget($key)
    {
        Cache::forget($key);
    }

    public static function forgetByTag($tag)
    {
        Cache::forget($tag);
    }

    public static function has($key)
    {
        return Cache::has($key);
    }

    public static function tag($key, $tag)
    {
        Cache::tags($tag)->put($key, Cache::get($key), now()->addDays(7));
    }

    public static function setTimedBasedItem($key, $time, $value)
    {
        $value = Cache::remember($key, 3600, function () {
            return 'Hello, World!';
        });
    }
}
