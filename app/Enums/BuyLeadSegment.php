<?php

namespace App\Enums;

enum BuyLeadSegment: string
{
    use Enumable;

    case SIC = 'sic';
    case NON_SIC = 'non-sic';

    public function label()
    {
        return match ($this) {
            self::SIC => 'SIC',
            self::NON_SIC => 'Non SIC'
        };
    }
}
