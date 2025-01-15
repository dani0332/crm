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
            self::SIC => 'SIC Ecom leads',
            self::NON_SIC => 'Non SIC Ecom leads',
            self::SIC_REVIVAL => 'Revival Leads',
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

    public static function withLabels($quoteTypeId = null): array
    {
        $values = [];
        $caseList = collect(self::cases());
        if ($quoteTypeId == QuoteTypeId::Health) {
            $caseList = collect($caseList)->whereNotIn('value', self::SIC_REVIVAL->value);
        }
        foreach ($caseList as $case) {
            $values[] = [
                'value' => $case->value,
                'label' => $case->label(),
            ];
        }

        return $values;
    }
}
