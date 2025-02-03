<?php

namespace App\Enums;

enum SavingsPurposeEnum: string
{
    use Enumable;

    case PERSONAL_COVER = 'personal_cover';
    case FAMILY_PROTECTION = 'family_protection';
    case PENSION = 'pension';
    case REGULAR_SAVINGS = 'regular_savings';
    case CHILD_EDUCATION = 'child_education';
    case MORTAGE_COVER = 'mortage_cover';
    case OTHERS = 'others';
}
