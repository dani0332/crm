<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class MonthNameEnum extends Enum
{
    public const January = 'January';
    public const February = 'February';
    public const March = 'March';
    public const April = 'April';
    public const May = 'May';
    public const June = 'June';
    public const July = 'July';
    public const August = 'August';
    public const September = 'September';
    public const October = 'October';
    public const November = 'November';
    public const December = 'December';

    public static function all()
    {
        return [
            self::January,
            self::February,
            self::March,
            self::April,
            self::May,
            self::June,
            self::July,
            self::August,
            self::September,
            self::October,
            self::November,
            self::December,
        ];
    }
}
