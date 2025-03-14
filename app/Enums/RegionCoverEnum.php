<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class RegionCoverEnum extends Enum
{
    public const WORLDWIDE_EXCL_US_CANADA = 'Worldwide (excl. US/Canada)';
    public const WORLDWIDE_INCL_US_CANADA = 'Worldwide (incl. US/Canada)';
    public const SCHENGEN = 'Schengen Countries';
    public const NORWAY = 'Norwegian';
    public const UNITED_STATES = 'American';
    public const FRANCE = 'French';

    /**
     * const @var array
     */
    const REGION_COVER_LIST = [
        self::WORLDWIDE_EXCL_US_CANADA,
        self::WORLDWIDE_INCL_US_CANADA,
        self::SCHENGEN,
    ];
}
