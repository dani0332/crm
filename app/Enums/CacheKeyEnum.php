<?php

namespace App\Enums;

enum CacheKeyEnum: string
{
    case HOME_LOOKUPS = 'home_lookups';

    public function expiry()
    {
        return match ($this) {
            self::HOME_LOOKUPS => now()->endOfDay(),
            default => now()->addHour(),
        };
    }
}
