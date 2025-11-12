<?php

namespace App\Enums;

enum CacheKeyEnum: string
{
    case HOME_LOOKUPS = 'home_lookups';
    case SAVINGS_QUOTE_LOOKUPS = 'savings_quote_lookups';
    case SMART_PHONE_QUOTE_LOOKUPS = 'smart_phone_quote_lookups';
    public function expiry()
    {
        return match ($this) {
            self::HOME_LOOKUPS => now()->endOfDay(),
            self::SAVINGS_QUOTE_LOOKUPS => now()->endOfDay(),
            self::SMART_PHONE_QUOTE_LOOKUPS => now()->endOfDay(),
            default => now()->addHour(),
        };
    }
}
