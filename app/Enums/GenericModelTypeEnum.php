<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class GenericModelTypeEnum extends Enum
{
    const INSURANCE_PROVIDER = 'insuranceprovider';
    const CAR_PLAN = 'carplan';
    const CAR_PLAN_COVERAGE = 'carplancoverage';
    const CAR_PLAN_ADDON = 'carplanaddon';
    const CAR_PLAN_ADDON_OPTION = 'carplanaddonoption';
    const APPLICATION_STORAGE = 'applicationstorage';
    const TEAMS = 'team';
    const LEAD_STATUS = 'leadstatus';
    const TIER = 'tier';
    const QUADRANT = 'quadrant';
    const RULE = 'rule';
}
