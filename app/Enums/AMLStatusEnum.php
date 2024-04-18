<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class AMLStatusEnum extends Enum
{
    const AMLPending = 1;
    const AMLScreeningCleared = 2;
    const AMLScreeningFailed = 3;

}
