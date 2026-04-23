<?php

namespace App\Enums;

enum GenderEnum: string
{
    use Enumable;

    case MALE = 'male';
    case FEMALE = 'female';

    case MALE_SHORT = 'M';
    case FEMALE_SHORT = 'F';

    // Legacy gender codes
    case LEGACY_MALE = 'Male';
    case LEGACY_FEMALE = 'Female';
    case LEGACY_FEMALE_SHORT = 'FS';
    case LEGACY_FEMALE_MARRIED = 'FM';
}
