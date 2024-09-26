<?php

namespace App\Exports\Reports;

use Maatwebsite\Excel\Events\AfterSheet;

class ActivePoliciesReportExport extends BaseReportsExport
{
    public function headings(): array
    {
        return [
            'Insurer',
            'Line of Business',
            'Active Policy Count',
            'Price (VAT applicable)',
            'Price (VAT not applicable)',
        ];
    }

    public function map($quote): array
    {
        return [
            $quote->insurer ?? 'N/A',
            $quote->line_of_business ?? 'N/A',
            $this->resolveNumberFormat($quote->active_policy_count ?? 0),
            $this->resolveNumberFormat($quote->price_with_vat ?? 0),
            $this->resolveNumberFormat($quote->price_without_vat ?? 0),
        ];
    }

    public static function afterSheet(AfterSheet $event)
    {
        self::performSum($event, ['C', 'D', 'E']);
    }
}
