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
}
