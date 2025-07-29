<?php

namespace App\Enums;

enum RangeLookupKeyEnums: string
{
    case POSSESSION_TYPE = 'possession-type';
    case ACCOMMODATION_TYPE = 'accommodation-type';
    case OWNER_OCCUPANCY_TYPE = 'owner-occupancy-type';
    case COVERAGE_TYPE = 'coverage-type';
    case COVERAGE_POSSESSION_TYPE = 'coverage-possession-type';
    case CONTENT_VALUES = 'content-values';
    case PERSONAL_BELONGING_VALUES = 'personal-belonging-values';
}
