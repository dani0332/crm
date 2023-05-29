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
    public const ACTIVE = 1;
    public const INACTIVE = 0;
    public const CAR_LEAD_ALLOCATION_JOB_SWITCH = 'CAR_LEAD_ALLOCATION_JOB_SWITCH';
    public const CAR_LEAD_ALLOCATION_START_TIME = 'CAR_LEAD_ALLOCATION_START_TIME';
    public const SATURDAY_CAP_RESET_TIME = 'SATURDAY_CAP_RESET_TIME';
    public const NORMAL_CAP_RESET_TIME = 'NORMAL_CAP_RESET_TIME';
    public const CAR_LEAD_ALLOCATION_MASTER_SWITCH = 'CAR_LEAD_ALLOCATION_MASTER_SWITCH';
    public const SIB_CAR_DRIP_LIST_ID = 'SIB_CAR_DRIP_LIST_ID';
    public const SIB_HEALTH_EBP_LIST_ID = 'SIB_HEALTH_EBP_LIST_ID';
    public const RENEWAL_ALLOCATION_LEAD_EMAIL_CC = 'RENEWAL_ALLOCATION_LEAD_EMAIL_CC';
    public const HEALTH_MANUAL_ASSIGNMENT_USER_BYPASS = 'HEALTH_MANUAL_ASSIGNMENT_USER_BYPASS';
    public const LMS_INTRO_EMAIL_BCC = 'LMS_INTRO_EMAIL_BCC';
    public const LMS_INTRO_EMAIL_ATTACHMENT_URL = 'LMS_INTRO_EMAIL_ATTACHMENT_URL';
}
