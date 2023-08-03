<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class RuleTypeEnum extends Enum
{
    public const LEAD_SOURCE    =   '1';
    public const CAR_MAKE       =   '2';
    public const CAR_MODEL      =   '3';

    /**
     * const @var array
     */
    const RULE_TYPE_LIST = [
        self::LEAD_SOURCE,
        self::CAR_MAKE,
        self::CAR_MODEL
    ];
}
