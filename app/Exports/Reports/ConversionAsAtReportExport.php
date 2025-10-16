<?php

namespace App\Exports\Reports;

use App\Contracts\CsvExportableInterface;
use App\Services\ConversionAsAtReportService;
use App\Services\Logger\LoggerService;
use App\Traits\ModernCsvExportable;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ConversionAsAtReportExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    protected Collection $columnTotals;
    protected Collection $headers;
    protected bool $includeUnassignedLeads;
    protected ?string $displayBy;
    protected int $columnOffset;

    public function __construct(
        private ConversionAsAtReportService $conversionAsAtReportService,
        private array $requestParams
    ) {
        request()->merge($this->requestParams);

        // Initialize common properties
        $this->includeUnassignedLeads = ($this->requestParams['includeUnassignedLeads'] ?? 'no') === 'yes';
        $this->displayBy = $this->requestParams['displayBy'] ?? null;
        $this->columnOffset = (! empty($this->displayBy) || $this->includeUnassignedLeads) ? 1 : 0;

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

        // Show title column if EITHER displayBy is set OR includeUnassignedLeads is enabled
        if (! empty($this->displayBy) || $this->includeUnassignedLeads) {
            if (! empty($this->displayBy)) {
                $titleHeader = str_replace('_', ' ', $this->displayBy);
                $titleHeader = ucwords($titleHeader);
            } else {
                $titleHeader = 'Assignment';
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
        LoggerService::info('processChunkedQuery Start');

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

        // Add title column if needed
        if ($this->columnOffset > 0) {
            if (! empty($this->displayBy)) {
                $titleValue = $record->{$this->displayBy} ?? 'N/A';
            } else {
                $titleValue = 'Assigned Leads';
            }
            $row = collect([$titleValue])->merge($baseData);
        } else {
            $row = collect($baseData);
        }

        foreach ($this->columnTotals as $index => $field) {
            $sumColumns = [4 + $this->columnOffset, 5 + $this->columnOffset, 6 + $this->columnOffset];

            if (is_numeric($row->get($index)) && in_array($index + 1, $sumColumns)) {
                $this->columnTotals->put($index, ((float) $this->columnTotals->get($index, 0) + (float) ($row->get($index) ?? 0)));
            }
        }

        return $row->values()->toArray();
    }

    private function postDataRows($stream)
    {
        $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
        $totalsRow = $this->getEmptyRow();
        $request = request()->merge($this->requestParams);
        $unassignedLeadsCount = $this->conversionAsAtReportService->getUnassignedLeadsCount($request);

        // Copy accumulated totals to totals row
        foreach ($this->columnTotals as $index => $key) {
            $totalsRow[$index] = $this->resolveNumberFormat($this->columnTotals->get($index));
        }

        // Add unassigned leads row BEFORE totals row if enabled
        if ($this->includeUnassignedLeads && $unassignedLeadsCount > 0) {
            $unassignedRow = array_fill(0, count($this->headers), 0);

            $unassignedRow[0] = 'Unassigned Leads';
            $unassignedRow[1] = Carbon::parse($this->requestParams['createdAtDate'][0])->format($dateFormat) ?? 'N/A';
            $unassignedRow[2] = Carbon::parse($this->requestParams['createdAtDate'][1])->format($dateFormat) ?? 'N/A';
            $unassignedRow[3] = Carbon::parse($this->requestParams['asAtDate'])->format($dateFormat) ?? 'N/A';

            $unassignedRow[4] = $this->resolveNumberFormat($unassignedLeadsCount);

            fputcsv($stream, $unassignedRow);
        }

        // Calculate totals with correct column positions
        $totalLeadsIndex = 3 + $this->columnOffset;
        $badLeadsIndex = 4 + $this->columnOffset;
        $saleLeadsIndex = 5 + $this->columnOffset;
        $grossConversionIndex = 6 + $this->columnOffset;
        $netConversionIndex = 7 + $this->columnOffset;

        $totalLeads = $this->columnTotals->get($totalLeadsIndex, 0);
        $badLeads = $this->columnTotals->get($badLeadsIndex, 0);
        $saleLeads = $this->columnTotals->get($saleLeadsIndex, 0);

        // Add unassigned leads to total leads count if included
        if ($this->includeUnassignedLeads && $unassignedLeadsCount > 0) {
            $totalLeads += $unassignedLeadsCount;
            $totalsRow[$totalLeadsIndex] = $this->resolveNumberFormat($totalLeads);
        }

        // Set totals row label in first column (if title column exists)
        if ($this->columnOffset > 0) {
            $totalsRow[0] = 'Totals';
        }

        // Calculate Gross Conversion % = ((saleLeads / (totalLeads - badLeads)) * 100)
        if ($totalLeads > 0) {
            $totalsRow[$grossConversionIndex] = $this->resolveNumberFormat(($saleLeads / $totalLeads) * 100);
        } else {
            $totalsRow[$grossConversionIndex] = 0;
        }

        // Calculate Net Conversion % = ((saleLeads / totalLeads) * 100) 2
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
