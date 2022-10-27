<?php

namespace App\Enums;

use BenSampo\Enum\Enum;
use ReflectionClass;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class ShortQuoteTypeId extends Enum
{
    const CAR = 1;
    const HOM = 2;
    const HEA = 3;
    const LIF = 4;
    const BUS = 5;
    const BIK = 6;
    const YAC = 7;
    const TRA = 8;
    const PET = 9;
}
