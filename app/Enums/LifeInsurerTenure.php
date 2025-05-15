<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class LifeInsurerTenure extends Enum
{
    const FIXED_TERM_INSURANCE = 1;
    const WHOLE_LIFE_INSURANCE = 2;
    const SAVINGS = 3;
    const ENDOWMENT_INSURANCE = 4;
    const LIFE = 6;
}
