<?php

namespace App\Enums;

enum TeamCategoryEnum: string
{
    use Enumable;

    case AUH = 'AUH';
    case NON_AUH = 'Non AUH';
}
