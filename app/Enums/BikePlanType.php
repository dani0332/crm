<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class BikePlanType extends Enum
{
    public const TPL = 'TPL';
    public const COMP = 'COMP';
    public const AGENCY = 'AGENCY';
    public const NONAGENCY = 'NON-AGENCY';
}
