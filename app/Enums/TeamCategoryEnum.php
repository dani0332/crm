<?php

namespace App\Enums;

enum TeamCategoryEnum: string
{
    use Enumable;

    case SIC = 'SIC';
    case AUH = 'AUH';
    case NON_AUH = 'Non AUH';
}