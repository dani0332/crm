<?php

namespace App\Enums;

enum MaritalStatusIdEnum: int
{
    // Inactive statuses
    case UNMARRIED_PARTNER = 5;

    // Active statuses
    case SINGLE = 1;
    case MARRIED = 2;
    case DIVORCED = 3;
    case WIDOWED = 4;
}
