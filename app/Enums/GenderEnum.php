<?php

namespace App\Enums;

enum GenderEnum: string
{
    use Enumable;

    case MALE = 'male';
    case FEMALE = 'female';

    const MALE_SHORT = 'M';
    const FEMALE_SHORT = 'F';

    // Legacy gender codes
    const LEGACY_MALE = 'Male';
    const LEGACY_FEMALE = 'Female';
    const LEGACY_FEMALE_SHORT = 'FS';
    const LEGACY_FEMALE_MARRIED = 'FM';
}
