<?php

namespace App\Enums;

enum CacheKeyEnum: string
{
    case HOME_LOOKUPS = 'home_lookups';
    case SAVINGS_QUOTE_LOOKUPS = 'savings_quote_lookups';
    case CYBER_QUOTE_LOOKUPS = 'cyber_quote_lookups';
    case SUB_SOURCES = 'sub_sources';
    case HRM_API_ACCESS_TOKEN = 'hrm_api_access_token';

    case CLAIM_CACHE_DROPDOWN_DATA_KEY = 'claim_dropdown_data_key';
    case CLAIM_CACHE_COMPLAINT_STATUSES_KEY = 'claim_complaint_statuses_key';
    case CLAIM_CACHE_QUOTE_TYPE_KEY = 'claim_quote_type_key';

    public function expiry()
    {
        return match ($this) {
            self::CLAIM_CACHE_DROPDOWN_DATA_KEY,
            self::CLAIM_CACHE_QUOTE_TYPE_KEY,
            self::CLAIM_CACHE_COMPLAINT_STATUSES_KEY => now()->addHours(4),
            self::HOME_LOOKUPS => now()->endOfDay(),
            self::SAVINGS_QUOTE_LOOKUPS => now()->endOfDay(),
            self::CYBER_QUOTE_LOOKUPS => now()->endOfDay(),
            self::SUB_SOURCES => now()->endOfDay(),
            self::HRM_API_ACCESS_TOKEN => now()->addHour(),
            default => now()->addHour(),
        };
    }
}
