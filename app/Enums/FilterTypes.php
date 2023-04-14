<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class FilterTypes extends Enum
{
    public const EXACT = 'exact';
    public const FREE = 'free';
    public const DATE = 'date';
    public const DATE_BETWEEN = 'date_between';
    public const IN = 'in';
    public const NULL_CHECK = 'null_check';
}
