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
    case HEALTH_INSURE_OPTIONS_KEY = 'health_insure_options_key';
    case POLICY_HOLDER_KEY = 'policy_holder_key';
    case POLICY_HOLDER_CATEGORY_KEY = 'policy_holder_category_key';
    case VISA_CATEGORY_KEY = 'visa_category_key';
    case MEMBER_CATEGORY_DISPLAY_MAP_KEY = 'member_category_display_map_key';
    case MEMBER_RELATIONS_KEY = 'member_relations_key';
    case HEALTH_MEMBER_RELATIONS_KEY = 'health_member_relations_key';
    case DOMESTIC_WORKER_RELATIONS_KEY = 'domestic_worker_relations_key';
    case MEMBER_RELATION_DISPLAY_MAP_KEY = 'member_relation_display_map_key';
    case GENDER_KEY = 'gender_key';
    case HEALTH_GENDER_DISPLAY_MAP_KEY = 'health_gender_display_map_key';
    case MARITAL_STATUS_KEY = 'marital_status_key';
    case NATIONALITIES_GCC_IDS = 'nationalities_gcc_ids';
    case NATIONALITIES_UAE_IDS = 'nationalities_uae_ids';

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
