<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class AMLScreeningTypeEnum extends Enum
{
    const BRIDGER = 'BRIDGER';
    const INSURER_AXA = 'INSURER_AXA';
}
