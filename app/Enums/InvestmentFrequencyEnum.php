<?php

namespace App\Enums;

use App\Enums\Enumable;

enum InvestmentFrequencyEnum: string
{
    use Enumable;

    case REGULAR = 'regular';
    case LUMPSUM = 'lumpsum';
}
