<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class IMCRMSearchTypesEnum extends Enum
{
    const LIKE_SEARCH = 'likeSearch';
    const EQUAL_SEARCH = 'equalSearch';
    const DATE_RANGE = 'dateRange';
    const MULTI_SEARCH = 'multiSearch';
    const NOT_EQUAL = 'notequal';
    const NULL = 'null';
    const NOT_NULL = 'notnull';
}
