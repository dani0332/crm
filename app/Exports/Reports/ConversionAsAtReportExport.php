<?php

namespace App\Exports\Reports;

use App\Contracts\CsvExportableInterface;
use App\Services\ConversionAsAtReportService;
use App\Traits\ModernCsvExportable;
use Illuminate\Support\Collection;

class ConversionAsAtReportExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    protected Collection $columnTotals;
    protected Collection $headers;

    public function __construct(
        private ConversionAsAtReportService $conversionAsAtReportService,
        private array $requestParams
    ) {
        request()->merge($this->requestParams);

        // Initialize dynamic headers based on displayBy parameter
        $this->initializeHeaders();

        $this->columnTotals = collect();

        $totalsRow = $this->getEmptyRow();

        $totalsRow[0] = 'Totals';

        $this->columnTotals = collect($totalsRow);
    }

    /**
     * Initialize headers dynamically based on displayBy parameter
     */
    private function initializeHeaders()
    {
        $baseHeaders = [
            'Start Date',
            'End Date',
            'As At Date',
            'Total Leads',
            'Bad Leads',
            'Sale Leads',
            'Gross Conversion %',
            'Net Conversion %',
        ];

        $displayBy = $this->requestParams['displayBy'] ?? null;
        $includeUnassignedLeads = ($this->requestParams['includeUnassignedLeads'] ?? 'no') === 'yes';

        // Show title column if EITHER displayBy is set OR includeUnassignedLeads is enabled
        if (! empty($displayBy) || $includeUnassignedLeads) {
            if (! empty($displayBy)) {
                // Use displayBy as header name
                $titleHeader = str_replace('_', ' ', $displayBy);
                $titleHeader = ucwords($titleHeader);
            } else {
                // Generic title when only unassigned leads is enabled
                $titleHeader = 'Assignment Type';
            }

            $this->headers = collect([$titleHeader])->merge($baseHeaders);
        } else {
            $this->headers = collect($baseHeaders);
        }
    }

    /**
     * Get the data collection for CSV export
     */
    public function collection(array $requestParams = []): Collection
    {
        $request = request()->merge($requestParams);
        $data = $this->conversionAsAtReportService->getReportData($request);

        return $data;
    }

    /**
     * Get the query builder instance for chunked processing
     */
    public function getQuery(array $requestParams = []): ?\Illuminate\Database\Eloquent\Builder
    {
        $request = request()->merge($requestParams);

        // Note: If ConversionAsAtReportService doesn't have getReportQueryBuilder method,
        // we'll handle chunked processing through collection method

        return $this->conversionAsAtReportService->getReportQueryBuilder($request);
    }

    public function processChunkedQuery($query, array $requestParams, $stream): int
    {
        $totalRecords = 0;
        $chunkSize = 1000;
        info('processChunkedQuery Start');

        $requestParams = request()->merge($requestParams);

        $query->chunk($chunkSize, function ($chunk) use (&$totalRecords, $requestParams, $stream) {

            $processedData = $this->conversionAsAtReportService->mapConversionData($chunk, $requestParams);

            // Now process ALL records in the chunk (just like the download path does)
            foreach ($processedData as $record) {
                fputcsv($stream, $this->map($record));
                $totalRecords++;
            }
        });

        // Write totals rows to file which were calculated during map()
        $this->postDataRows($stream);

        return $totalRecords;
    }

    public function headings(): array
    {
        return $this->headers->toArray();
    }

    public function map($record): array
    {
        $displayBy = $this->requestParams['displayBy'] ?? null;
        $includeUnassignedLeads = ($this->requestParams['includeUnassignedLeads'] ?? 'no') === 'yes';

        $baseData = [
            $record->start_date ?? 'N/A',
            $record->end_date ?? 'N/A',
            $record->as_at_date ?? 'N/A',
            $this->resolveNumberFormat($record->total_leads ?? 0),
            $this->resolveNumberFormat($record->bad_leads ?? 0),
            $this->resolveNumberFormat($record->sale_leads ?? 0),
            $this->resolveNumberFormat($record->gross_conversion ?? 0),
            $this->resolveNumberFormat($record->net_conversion ?? 0),
        ];

        // Add title column if EITHER displayBy is set OR includeUnassignedLeads is enabled
        if (! empty($displayBy) || $includeUnassignedLeads) {
            if (! empty($displayBy)) {
                // Add displayBy value
                $titleValue = $record->{$displayBy} ?? 'N/A';
            } else {
                // Generic value when only unassigned leads is enabled
                $titleValue = '';
            }
            $row = collect([$titleValue])->merge($baseData);
        } else {
            $row = collect($baseData);
        }

        foreach ($this->columnTotals as $index => $field) {
            // Calculate offset: 1 if title column exists, 0 if not
            $offset = (! empty($displayBy) || $includeUnassignedLeads) ? 1 : 0;
            $sumColumns = [4 + $offset, 5 + $offset, 6 + $offset]; // total_leads, bad_leads, sale_leads

            if (is_numeric($row->get($index)) && in_array($index + 1, $sumColumns)) {
                $this->columnTotals->put($index, ((float) $this->columnTotals->get($index, 0) + (float) ($row->get($index) ?? 0)));
            }
        }

        return $row->values()->toArray();
    }

    private function postDataRows($stream)
    {
        $totalsRow = $this->getEmptyRow();

        $request = request()->merge($this->requestParams);
        $unassignedLeadsCount = $this->conversionAsAtReportService->getUnassignedLeadsCount($request);

        // Check if we should include unassigned leads
        $includeUnassignedLeads = ($this->requestParams['includeUnassignedLeads'] ?? 'no') === 'yes';

        // Calculate offset: 1 if title column exists, 0 if not
        $offset = (! empty($this->requestParams['displayBy']) || $includeUnassignedLeads) ? 1 : 0;

        // Copy accumulated totals to totals row
        foreach ($this->columnTotals as $index => $key) {
            $totalsRow[$index] = $this->resolveNumberFormat($this->columnTotals->get($index));
        }

        // Add unassigned leads row BEFORE totals row if enabled
        if ($includeUnassignedLeads && $unassignedLeadsCount > 0) {

            // Create row with all zeros, then override specific values
            $unassignedRow = array_fill(0, count($this->headers), 0);

            // Always add title column since includeUnassignedLeads is true
            $unassignedRow[0] = 'Unassigned Leads';
            $unassignedRow[1] = $this->requestParams['createdAtDate'][0] ?? 'N/A';
            $unassignedRow[2] = $this->requestParams['createdAtDate'][1] ?? 'N/A';
            $unassignedRow[3] = $this->requestParams['asAtDate'] ?? 'N/A';
            $unassignedRow[4] = $this->resolveNumberFormat($unassignedLeadsCount);
            // Other indexes remain 0

            fputcsv($stream, $unassignedRow);
        }

        // Calculate totals with correct column positions
        $totalLeadsIndex = 3 + $offset;
        $badLeadsIndex = 4 + $offset;
        $saleLeadsIndex = 5 + $offset;
        $grossConversionIndex = 6 + $offset;
        $netConversionIndex = 7 + $offset;

        $totalLeads = $this->columnTotals->get($totalLeadsIndex, 0);
        $badLeads = $this->columnTotals->get($badLeadsIndex, 0);
        $saleLeads = $this->columnTotals->get($saleLeadsIndex, 0);

        // Add unassigned leads to total leads count if included
        if ($includeUnassignedLeads && $unassignedLeadsCount > 0) {
            $totalLeads += $unassignedLeadsCount;
            $totalsRow[$totalLeadsIndex] = $this->resolveNumberFormat($totalLeads);
        }

        // Set totals row label in first column
        $totalsRow[0] = 'Totals';

        // Calculate Gross Conversion % = ((saleLeads / totalLeads) * 100)
        if ($totalLeads > 0) {
            $totalsRow[$grossConversionIndex] = $this->resolveNumberFormat(($saleLeads / $totalLeads) * 100);
        } else {
            $totalsRow[$grossConversionIndex] = 0;
        }

        // Calculate Net Conversion % = ((saleLeads / (totalLeads - badLeads)) * 100)
        $validLeads = $totalLeads - $badLeads;
        if ($validLeads > 0) {
            $totalsRow[$netConversionIndex] = $this->resolveNumberFormat(($saleLeads / $validLeads) * 100);
        } else {
            $totalsRow[$netConversionIndex] = 0;
        }

        fputcsv($stream, $totalsRow);
    }

    private function getEmptyRow(): array
    {
        return array_fill(0, count($this->map((object) [])), '');
    }
}
