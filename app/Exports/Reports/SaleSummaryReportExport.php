<?php

namespace App\Exports\Reports;

use App\Contracts\CsvExportableInterface;
use App\Services\Logger\LoggerService;
use App\Services\Reports\SaleSummaryReportService;
use App\Traits\ModernCsvExportable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SaleSummaryReportExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    private string $groupByColumn = 'advisor';
    protected Collection $columnTotals;

    public function __construct(
        private SaleSummaryReportService $saleSummaryReportService,
        private array $requestParams
    ) {
        request()->merge($this->requestParams);

        if (request()->filled('groupBy')) {
            $this->groupByColumn = request()->groupBy;
            // Initialize totals for numeric columns
        }

        $this->columnTotals = collect([
            'total_policies' => 0,
            'total_endorsements' => 0,
            'total_transaction' => 0,
            'price_vat_applicable' => 0,
            'total_vat' => 0,
            'price_vat_not_applicable' => 0,
            'discount' => 0,
            'commission_vat_applicable' => 0,
            'commission_vat' => 0,
            'commission_vat_not_applicable' => 0,
            'endorsements_amount' => 0,
            'total_price' => 0,
        ]);
    }

    /**
     * Get the data collection for CSV export
     */
    public function collection(array $requestParams = []): Collection
    {
        $request = request()->merge($requestParams);

        $data = $this->saleSummaryReportService->getReportData($request);

        return $data;
    }

    /**
     * Get the query builder instance for chunked processing
     */
    public function getQuery(array $requestParams = []): ?Builder
    {
        $request = request()->merge($requestParams);

        return $this->saleSummaryReportService->getReportQueryBuilder($request);
    }

    public function processChunkedQuery($query, array $requestParams, $stream): int
    {
        $totalRecords = 0;
        $chunkSize = 1000;

        LoggerService::info('processChunkedQuery Start');

        $requestParams = request()->merge($requestParams);

        $endorsementsData = $this->saleSummaryReportService->getEndorsementsData($requestParams);

        foreach ($query->cursor()->chunk($chunkSize) as $chunk) {
            $chunk = collect($chunk);
            $processedData = $this->saleSummaryReportService->processEndorsementsData($chunk, $endorsementsData, $requestParams);

            $this->saleSummaryReportService->formatData($processedData);

            foreach ($processedData as $record) {
                fputcsv($stream, $this->map($record));
                $totalRecords++;
            }
        }

        // Write totals rows to file which were calculated during map()
        $this->postDataRows($stream);

        return $totalRecords;
    }

    public function headings(): array
    {
        $groupByColumn = match ($this->groupByColumn) {
            'support_user' => 'OE/AE',
            'branch_name' => 'branch',
            'pqa' => 'Pre-Qualification Advisor',
            default => $this->groupByColumn,
        };
        $headings = [
            ucwords(str_replace('_', ' ', $groupByColumn)),
        ];

        if (in_array($this->groupByColumn, ['advisor', 'support_user'])) {
            $headings[] = 'Department';
        }

        $headings = [
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

        if ($this->groupByColumn != 'branch_name') {
            $headings[] = 'Branch';
        }

        return $headings;
    }

    public function map($quote): array
    {
        $groupBy = $this->groupByColumn;
        $groupByColumnMapping = [
            'policy_issuer' => 'policy_issuer_name',
            'customer_group' => 'customer_name',
        ];

        $groupBy = $groupByColumnMapping[$groupBy] ?? $groupBy;
        $values[] = $quote->{$groupBy} ?? 'N/A';

        if (in_array($this->groupByColumn, ['advisor', 'support_user'])) {
            $values[] = $quote->department ?? 'N/A';
        }

        $numericValues = collect([
            'total_policies' => $this->resolveNumberFormat($quote->total_policies ?? 0),
            'total_endorsements' => $this->resolveNumberFormat($quote->total_endorsements ?? 0),
            'total_transaction' => $this->resolveNumberFormat($quote->total_transaction ?? 0),
            'price_vat_applicable' => $this->resolveNumberFormat($quote->price_vat_applicable ?? 0),
            'total_vat' => $this->resolveNumberFormat($quote->total_vat ?? 0),
            'price_vat_not_applicable' => $this->resolveNumberFormat($quote->price_vat_not_applicable ?? 0),
            'discount' => $this->resolveNumberFormat($quote->discount ?? 0),
            'commission_vat_applicable' => $this->resolveNumberFormat($quote->commission_vat_applicable ?? 0),
            'commission_vat' => $this->resolveNumberFormat($quote->commission_vat ?? 0),
            'commission_vat_not_applicable' => $this->resolveNumberFormat($quote->commission_vat_not_applicable ?? 0),
            'endorsements_amount' => $this->resolveNumberFormat($quote->endorsements_amount ?? 0),
            'total_price' => $this->resolveNumberFormat($quote->total_price ?? 0),
        ]);

        if ($this->groupByColumn != 'branch_name') {
            $numericValues->put('branch_name', $quote->branch_name ?? 'N/A');
        }

        foreach ($this->columnTotals->keys() as $field) {
            $this->columnTotals->put($field, $this->columnTotals->get($field, 0) + ($numericValues->get($field) ?? 0));
        }

        return [
            ...$values,
            ...$numericValues->values()->toArray(),
        ];
    }

    private function postDataRows($stream)
    {
        $totalsRow = array_fill(0, count($this->map((object) [])), '');
        $totalsRow[0] = 'Totals';
        // Adjust offset for: groupBy column (1) + optional department column (1) + branch column (1)
        $offset = 1;
        if (in_array($this->groupByColumn, ['advisor', 'support_user'])) {
            $offset = 2;
        }

        foreach ($this->columnTotals->keys() as $index => $key) {
            $totalsRow[$index + $offset] = $this->resolveNumberFormat($this->columnTotals->get($key));
        }
        fputcsv($stream, $totalsRow);
    }
}
