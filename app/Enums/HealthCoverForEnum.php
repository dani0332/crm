<?php

namespace App\Enums;

enum HealthCoverForEnum: int
{
    // Inactive covers
    case INDIVIDUAL = 1;
    case FAMILY = 2;

    // Active covers
    case MY_COMPANY = 3;
    case INDIVIDUAL_AND_FAMILIES = 4;
    case DOMESTIC_HELPER = 5;
}
