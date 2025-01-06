<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class InsurerProviderEnum extends Enum
{
    const GIG_INSURANCE = 'AXA';
    const QATAR_INSURANCE = 'QIC';
}
