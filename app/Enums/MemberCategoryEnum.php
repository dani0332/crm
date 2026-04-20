<?php

namespace App\Enums;

enum MemberCategoryEnum: int
{
    // Inactive categories
    case INVESTOR_PARTNER = 9;
    case GOLDEN_VISA = 10;
    case SELF_EMPLOYED_FREELANCE = 11;
    case DOMESTIC_WORKER = 12;
    case DEPENDENT_SPOUSE = 13;
    case DEPENDENT_CHILD = 14;
    case DEPENDENT_PARENT = 15;
    case DEPENDENT_SIBLING_OR_OTHER_RELATIVES = 16;
    case EMPLOYEE_1 = 17;
    case EMPLOYEE_2 = 18;

    // Active categories
    case EXPAT_DUBAI_VISA = 20;
    case EXPAT_NON_DUBAI_VISA = 21;
    case GCC_NATIONAL = 22;
    case UAE_NATIONAL = 23;
    case NEWBORN = 24;
}
