<?php

namespace App\Exports\Reports;

use App\Contracts\CsvExportableInterface;
use App\Services\Logger\LoggerService;
use App\Services\Reports\EndingPoliciesReportService;
use App\Traits\ModernCsvExportable;
use Illuminate\Support\Collection;

class EndingPoliciesReportExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    protected Collection $columnTotals;

    public function __construct(
        private EndingPoliciesReportService $endingPoliciesReportService,
        private array $requestParams
    ) {
        request()->merge($this->requestParams);

        $this->columnTotals = collect();

        $totalsRow = $this->getEmptyRow();

        $totalsRow[0] = 'Totals';

        $this->columnTotals = collect($totalsRow);
    }

    /**
     * Get the data collection for CSV export
     */
    public function collection(array $requestParams = []): Collection
    {
        $request = request()->merge($requestParams);

        return $this->endingPoliciesReportService->getReportData($request);
    }

    /**
     * Get the query builder instance for chunked processing
     */
    public function getQuery(array $requestParams = []): ?\Illuminate\Database\Eloquent\Builder
    {
        $request = request()->merge($requestParams);

        return $this->endingPoliciesReportService->getReportQueryBuilder($request);
    }
    public function headings(): array
    {
        return [
            'Customer Name',
            'Policy Number',
            'Insurer',
            'Currently Insured With',
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
            'IMCRM SUB-SOURCE',
        ];
    }

    public function map($quote): array
    {
        $row = collect([
            $quote->customer_name ?? 'N/A',
            $quote->policy_number ?? 'N/A',
            $quote->insurer ?? 'N/A',
            $quote->currently_insured_with_text ?? 'N/A',
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
            $quote->sub_source ?? 'N/A',
        ]);

        foreach ($this->columnTotals as $index => $field) {

            if (is_numeric($row->get($index))) {
                $this->columnTotals->put($index, ((float) $this->columnTotals->get($index, 0) + (float) ($row->get($index) ?? 0)));
            }
        }

        return $row->values()->toArray();
    }

    public function processChunkedQuery($query, array $requestParams, $stream): int
    {
        $totalRecords = 0;
        $chunkSize = 1000;

        LoggerService::info(__CLASS__.' processChunkedQuery Start');

        $query->chunk($chunkSize, function ($chunk) use (&$totalRecords, $stream) {
            $this->endingPoliciesReportService->formatData($chunk);

            // Now process ALL records in the chunk (just like the download path does)
            foreach ($chunk as $record) {
                fputcsv($stream, $this->map($record));
                $totalRecords++;
            }
        });

        $this->postDataRows($stream);

        return $totalRecords;
    }

    private function postDataRows($stream)
    {
        $totalsRow = $this->getEmptyRow();

        foreach ($this->columnTotals as $index => $key) {
            $totalsRow[$index] = $this->resolveNumberFormat($this->columnTotals->get($index));
        }

        fputcsv($stream, $totalsRow);
    }

    private function getEmptyRow(): array
    {
        return array_fill(0, count($this->map((object) [])), '');
    }
}
