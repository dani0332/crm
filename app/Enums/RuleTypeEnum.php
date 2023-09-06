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
    public const LEAD_SOURCE = '1';
    public const CAR_MAKE_MODEL = '2';

    /**
     * const @var array
     */
    const RULE_TYPE_LIST = [
        self::LEAD_SOURCE,
        self::CAR_MAKE_MODEL,
    ];
}
