<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class ApplicationStorageEnums extends Enum
{
    const ACTIVE = 1;
    const INACTIVE = 0;
    const CAR_LEAD_ALLOCATION_JOB_SWITCH = 'CAR_LEAD_ALLOCATION_JOB_SWITCH';
    const CAR_LEAD_ALLOCATION_START_TIME = 'CAR_LEAD_ALLOCATION_START_TIME';
    const SATURDAY_CAP_RESET_TIME = 'SATURDAY_CAP_RESET_TIME';
    const NORMAL_CAP_RESET_TIME = 'NORMAL_CAP_RESET_TIME';
    const CAR_LEAD_ALLOCATION_MASTER_SWITCH = 'CAR_LEAD_ALLOCATION_MASTER_SWITCH';
    const SIB_CAR_DRIP_LIST_ID = 'SIB_CAR_DRIP_LIST_ID';
    const SIB_HEALTH_EBP_LIST_ID = 'SIB_HEALTH_EBP_LIST_ID';
}
