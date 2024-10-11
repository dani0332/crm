<?php

namespace App\Exports\Reports;

use Maatwebsite\Excel\Events\AfterSheet;

class EndingPoliciesReportExport extends BaseReportsExport
{
    public function headings(): array
    {
        return [
            'Customer Name',
            'Policy Number',
            'Insurer',
            'Line Of Business',
            'Policy Start Date',
            'Policy Expiry Date',
            'Collected Amount',
            'Price (VAT applicable)',
            'Total VAT',
            'Price (VAT not applicable)',
            'Discount',
            'Total Price',
            'Pending Balance',
            'Commission (VAT applicable)',
            'VAT on Commission',
            'Commission (VAT not applicable)',
            'Policy Issuer ',
            'Advisor',
            'Lead Source',
            'Notes',
        ];
    }

    public function map($quote): array
    {
        return [
            $quote->customer_name ?? 'N/A',
            $quote->policy_number ?? 'N/A',
            $quote->insurer ?? 'N/A',
            $quote->line_of_business ?? 'N/A',
            $quote->policy_start_date ?? 'N/A',
            $quote->policy_end_date ?? 'N/A',
            $this->resolveNumberFormat($quote->collected_amount ?? 0),
            $this->resolveNumberFormat($quote->price_vat_applicable ?? 0),
            $this->resolveNumberFormat($quote->total_vat ?? 0),
            $this->resolveNumberFormat($quote->price_vat_not_applicable ?? 0),
            $this->resolveNumberFormat($quote->discount ?? 0),
            $this->resolveNumberFormat($quote->total_price ?? 0),
            $this->resolveNumberFormat($quote->pending_balance ?? 0),
            $this->resolveNumberFormat($quote->commission_vat_applicable ?? 0),
            $this->resolveNumberFormat($quote->commission_vat ?? 0),
            $this->resolveNumberFormat($quote->commission_vat_not_applicable ?? 0),
            $quote->policy_issuer ?? 'N/A',
            $quote->advisor ?? 'N/A',
            $quote->source ?? 'N/A',
            $quote->notes ?? 'N/A',
        ];
    }

    public static function afterSheet(AfterSheet $event)
    {
        self::performSum($event, ['G', 'H', 'I', 'J', 'K', 'L', 'K', 'M', 'N', 'O', 'P']);
    }
}
