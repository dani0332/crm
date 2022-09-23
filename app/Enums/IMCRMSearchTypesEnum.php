<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class IMCRMSearchTypesEnum extends Enum
{
    const LikeSearch = 'likeSearch';
    const EqualSearch = 'equalSearch';
    const DateRange = 'dateRange';
    const MultiSearch = 'multiSearch';
}
