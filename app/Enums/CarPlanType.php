<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class CarPlanType extends Enum
{
    const TPL = 'TPL';
    const COMP = 'COMP';
    const NONAGENCY = 'NON-AGENCY';
}
