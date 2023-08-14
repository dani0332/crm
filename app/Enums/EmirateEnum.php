<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class EmirateEnum extends Enum
{
    const AJMAN = 1;
    const DUBAI = 2;
    const FUJAIRAH = 3;
    const RAS_AL_KHAIMAH = 4;
    const SHARJAH = 5;
    const UMM_AL_QUWAIN = 6;
    const ABU_DHABI = 7;
}
