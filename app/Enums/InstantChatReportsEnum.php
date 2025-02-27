<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class InstantChatReportsEnum extends Enum
{
    public const DETAILED_REPORT = 'Detailed';
    public const CONSOLIDATED_REPORT = 'Summary';
}
