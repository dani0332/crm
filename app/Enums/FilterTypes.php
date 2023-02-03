<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class FilterTypes extends Enum
{
    public const EXACT = 'exact';
    public const FREE = 'free';
    public const DATE = 'date';
    public const DATE_BETWEEN = 'date_between';
}
