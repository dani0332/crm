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
    public const AMLPending = 1;
    public const AMLScreeningCleared = 2;
    public const AMLScreeningFailed = 3;
}
