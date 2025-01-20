<?php

namespace App\Exports\Reports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Events\AfterSheet;

class SaleSummaryReportExport extends BaseReportsExport
{
    public function __construct(public Collection $data, public string $groupByColumn)
    {
        parent::__construct($data);
    }

    public function headings(): array
    {
        $headings = [
            ucwords(str_replace('_', ' ', $this->groupByColumn)),
        ];

        if (in_array($this->groupByColumn, ['advisor'])) {
            $headings[] = 'Department';
        }

        return [
            ...$headings,
            'Total Policies',
            'Total Endorsements',
            'Total Transactions',
            'Price (VAT applicable)',
            'Total VAT',
            'Price (VAT not applicable)',
            'Discount',
            'Commission (VAT applicable)',
            'VAT ON Commission',
            'Commission (VAT Not applicable)',
            'Total Endorsement Amount',
            'Total Price',
        ];
    }

    public function map($quote): array
    {
        $groupBy = $this->groupByColumn;
        $groupByColumnMapping = [
            'policy_issuer' => 'policy_issuer_name',
            'customer_group' => 'customer_name',
        ];

        $groupBy = $groupByColumnMapping[$groupBy] ?? $groupBy;

        $values = [
            $quote->{$groupBy} ?? 'N/A',
        ];

        if (in_array($this->groupByColumn, ['advisor'])) {
            $values[] = $quote->department ?? 'N/A';
        }

        return [
            ...$values,
            $this->resolveNumberFormat($quote->total_policies ?? 0),
            $this->resolveNumberFormat($quote->total_endorsements ?? 0),
            $this->resolveNumberFormat($quote->total_transaction ?? 0),
            $this->resolveNumberFormat($quote->price_vat_applicable ?? 0),
            $this->resolveNumberFormat($quote->total_vat ?? 0),
            $this->resolveNumberFormat($quote->price_vat_not_applicable ?? 0),
            $this->resolveNumberFormat($quote->discount ?? 0),
            $this->resolveNumberFormat($quote->commission_vat_applicable ?? 0),
            $this->resolveNumberFormat($quote->commission_vat ?? 0),
            $this->resolveNumberFormat($quote->commission_vat_not_applicable ?? 0),
            $this->resolveNumberFormat($quote->endorsements_amount ?? 0),
            $this->resolveNumberFormat($quote->total_price ?? 0),
        ];
    }

    public static function afterSheet(AfterSheet $event)
    {
        $commonColumns = ['C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'];

        $sumCoumns = ['B', ...$commonColumns];
        if (in_array($event->getConcernable()->groupByColumn, ['advisor'])) {
            $sumCoumns = [...$commonColumns, 'N'];
        }
        self::performSum($event, $sumCoumns);
    }
}
