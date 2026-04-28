<?php

namespace App\Enums;

enum GenderEnum: string
{
    use Enumable;

    case MALE = 'male';
    case FEMALE = 'female';
}
