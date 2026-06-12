<?php

namespace App\Enums;

enum CacheKeyEnum: string
{
    case HOME_LOOKUPS = 'home_lookups';
    case SAVINGS_QUOTE_LOOKUPS = 'savings_quote_lookups';
    case DEVICE_QUOTE_LOOKUPS = 'device_quote_lookups';
    case CYBER_QUOTE_LOOKUPS = 'cyber_quote_lookups';
    case SUB_SOURCES = 'sub_sources';
    case HRM_API_ACCESS_TOKEN = 'hrm_api_access_token';

    case CLAIM_MANAGERS_KEY = 'claim_managers_key';
    case CAR_MAKE_KEY = 'car_make_key';
    case CLAIM_COMPLAINT_STATUSES_KEY = 'claim_complaint_statuses_key';
    case CLAIM_STATUSES_KEY = 'claim_statuses_key';
    case CLAIM_SUB_STATUSES_KEY = 'claim_sub_statuses_key';
    case QUOTE_TYPE_KEY = 'quote_type_key';
    case CLAIM_REQUEST_TYPE_KEY = 'claim_request_type_key';
    case CLAIM_SERVICE_TYPE_KEY = 'claim_service_type_key';
    case CLAIM_TYPE_KEY = 'claim_type_key';
    case BUSINESS_TYPE_OF_INSURANCE_KEY = 'business_type_of_insurance_key';
    case CAR_MODEL_YEAR_KEY = 'car_model_year_key';
    case CONVERSION_OPTIMIZATION_DEFAULT_TEAM_FILTERS = 'conversion_optimization_default_team_filters';

    public function expiry()
    {
        return match ($this) {
            self::CLAIM_MANAGERS_KEY,
            self::CAR_MAKE_KEY,
            self::CLAIM_COMPLAINT_STATUSES_KEY,
            self::CLAIM_STATUSES_KEY,
            self::CLAIM_SUB_STATUSES_KEY,
            self::QUOTE_TYPE_KEY,
            self::CLAIM_REQUEST_TYPE_KEY,
            self::CLAIM_SERVICE_TYPE_KEY,
            self::CLAIM_TYPE_KEY,
            self::BUSINESS_TYPE_OF_INSURANCE_KEY,
            self::CAR_MODEL_YEAR_KEY => now()->addHours(4),
            self::CONVERSION_OPTIMIZATION_DEFAULT_TEAM_FILTERS => now()->addHours(4),
            self::HOME_LOOKUPS => now()->endOfDay(),
            self::SAVINGS_QUOTE_LOOKUPS => now()->endOfDay(),
            self::DEVICE_QUOTE_LOOKUPS => now()->endOfDay(),
            self::CYBER_QUOTE_LOOKUPS => now()->endOfDay(),
            self::SUB_SOURCES => now()->endOfDay(),
            self::HRM_API_ACCESS_TOKEN => now()->addHour(),
            default => now()->addHour(),
        };
    }
}
