<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class BirdFlowStatusEnum extends Enum
{
    public const POLICY_ISSUED = 'POLICY_ISSUED';
    public const ADDRESS_ADDED = 'ADDRESS_ADDED';
    public const ADDRESS_UPDATED = 'ADDRESS_UPDATED';

    // Send Policy Enums
    const GROUP_MEDICAL = 'GROUP_MEDICAL';
    const CAR_FLEET = 'CAR_FLEET';
    const TRADE = 'TRADE';
    const BUSINESS = 'BUSINESS';
    const PROFESSIONAL = 'PROFESSIONAL';
    const OTHER_BUSINESS = 'OTHER_BUSINESS';

    const BIRD_SUCCESS_STATUS_CODE = 201;
}
