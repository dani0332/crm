<?php

namespace App\Enums;

enum QuoteSegmentEnum: string
{
    use Enumable;

    case ALL = 'all';
    case SIC = 'sic';
    case NON_SIC = 'non-sic';
    case SIC_REVIVAL = 'sic-revival';
    case AIG = 'aig';
    case FIC = 'fic';
    case NON_FIC = 'non-fic';

    public function label()
    {
        return match ($this) {
            self::ALL => 'All',
            self::SIC => 'SIC Ecom leads',
            self::NON_SIC => 'Non SIC Ecom leads',
            self::SIC_REVIVAL => 'Revival Leads',
            self::AIG => 'AIG Leads',
            self::FIC => 'FIC Leads',
            self::NON_FIC => 'Non FIC Leads',
        };
    }

    public function tag()
    {
        return match ($this) {
            self::SIC => 'SIC',
            self::SIC_REVIVAL => 'Revival',
            self::NON_SIC => 'Non-SIC',
            self::AIG => 'AIG',
            self::FIC => 'FIC',
            self::NON_FIC => 'Non-FIC',

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
