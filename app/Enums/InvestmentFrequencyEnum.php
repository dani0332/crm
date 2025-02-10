<?php

namespace App\Enums;

enum InvestmentFrequencyEnum: string
{
    use Enumable;

    case REGULAR = 'regular';
    case LUMPSUM = 'lumpsum';
}
