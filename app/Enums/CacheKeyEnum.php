<?php

namespace App\Enums;

enum CacheKeyEnum: string
{
    case HOME_LOOKUPS = 'home_lookups';
    case SAVINGS_QUOTE_LOOKUPS = 'savings_quote_lookups';
    case DEVICE_QUOTE_LOOKUPS = 'device_quote_lookups';
    public function expiry()
    {
        return match ($this) {
            self::HOME_LOOKUPS => now()->endOfDay(),
            self::SAVINGS_QUOTE_LOOKUPS => now()->endOfDay(),
            self::DEVICE_QUOTE_LOOKUPS => now()->endOfDay(),
            default => now()->addHour(),
        };
    }
}
