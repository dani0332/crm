<?php

namespace App\Enums;

enum QuoteSegmentEnum: string
{
    use Enumable;

    case ALL = 'all';
    case SIC = 'sic';
    case NON_SIC = 'non-sic';

    public function label()
    {
        return match ($this) {
            self::ALL => 'All',
            self::SIC => 'SIC leads',
            self::NON_SIC => 'Non SIC leads',
        };
    }

    public function tag()
    {
        return match ($this) {
            self::SIC => 'SIC',
        };
    }
}
