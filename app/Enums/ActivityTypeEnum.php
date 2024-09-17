<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class ActivityTypeEnum extends Enum
{
    public const CALL_BACK = 'CALL';
    public const WHATS_APP = 'WHATSAPP';
}
