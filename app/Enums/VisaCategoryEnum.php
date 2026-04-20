<?php

namespace App\Enums;

enum VisaCategoryEnum: int
{
    case GOLDEN_VISA = 1;
    case INVESTOR_PARTNER = 2;
    case SELF_EMPLOYED_FREELANCE = 3;
    case SPONSORED_EMPLOYER_FAMILY = 4;
    case NEWBORN_BORN_IN_UAE = 5;
    case DOMESTIC_WORKER_VISA_FOR_UAE_NATIONALS = 6;
    case DOMESTIC_WORKER_VISA_FOR_NON_UAE_NATIONALS = 7;
}
