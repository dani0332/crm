<?php

namespace App\Enums;

enum QuoteSegmentEnum: string
{
    use Enumable;

    case ALL = 'all';
    case SIC = 'sic';
    case NON_SIC = 'non-sic';
    case SIC_REVIVAL = 'sic-revival';

    public function label()
    {
        return match ($this) {
            self::ALL => 'All',
            self::SIC => 'SIC leads',
            self::NON_SIC => 'Non SIC leads',
            self::SIC_REVIVAL => 'Revival',
        };
    }

    public function tag()
    {
        return match ($this) {
            self::SIC => 'SIC',
            self::SIC_REVIVAL => 'Revival',
            self::NON_SIC => 'Non-SIC',
        };
    }
}
