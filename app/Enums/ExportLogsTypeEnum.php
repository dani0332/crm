<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class ExportLogsTypeEnum extends Enum
{
    public const SEARCH_MODULE = 'search-module';
}
