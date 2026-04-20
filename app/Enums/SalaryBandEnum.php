<?php

namespace App\Enums;

enum SalaryBandEnum: int
{
    // Inactive salary bands
    case ABOVE_4000 = 2;

    // Active salary bands
    case BELOW_OR_EQ_4000 = 1;
    case BETWEEN_4001_AND_12000 = 3;
    case ABOVE_12000 = 4;
    case NO_SALARY_DEPENDENTS_OR_CHILDREN = 5;
    case NO_SALARY_COMMISSION_ONLY = 6;
}
