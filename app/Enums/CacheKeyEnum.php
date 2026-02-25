<?php

namespace App\Enums;

enum CacheKeyEnum: string
{
    case HOME_LOOKUPS = 'home_lookups';
    case SAVINGS_QUOTE_LOOKUPS = 'savings_quote_lookups';
    case SUB_SOURCES = 'sub_sources';
    case CYBER_QUOTE_LOOKUPS = 'cyber_quote_lookups';
    case HRM_API_ACCESS_TOKEN = 'hrm_api_access_token';
    public function expiry()
    {
        return match ($this) {
            self::HOME_LOOKUPS => now()->endOfDay(),
            self::SAVINGS_QUOTE_LOOKUPS => now()->endOfDay(),
            self::SUB_SOURCES => now()->endOfDay(),
            self::CYBER_QUOTE_LOOKUPS => now()->endOfDay(),
            self::HRM_API_ACCESS_TOKEN => now()->addHour(),
            default => now()->addHour(),
        };
    }
}
