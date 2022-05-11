<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class QuoteTypeShortCode extends Enum
{
    const BIK = "BIK";
    const BUS = "BUS";
    const CAR = "CAR";
    const HEA = "HEA";
    const HOM = "HOM";
    const LIF = "LIF";
    const TRA = "TRA";
    const YAC = "YAC";
}
