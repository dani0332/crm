<?php

namespace App\Enums;

use App\Enums\Enumable;

enum GenderEnum: string
{
    use Enumable;

    case MALE = 'male';
    case FEMALE = 'female';
}
