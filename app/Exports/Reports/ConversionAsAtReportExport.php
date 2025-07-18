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

    public function __construct(
        private ConversionAsAtReportService $conversionAsAtReportService,
        private array                       $requestParams
    )
    {
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
        return null;
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
                /*logger()->debug("record:".print_r([
                    '$record' => $record
                    ],1));*/
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
        return [
            'Start Date',
            'End Date',
            'As At Date',
            'Total Leads',
            'Bad Leads',
            'Sale Leads',
            'Net Conversion %',
            'Gross Conversion %',
        ];
    }

    public function map($record): array
    {
        $row = collect([
            $record->start_date ?? 'N/A',
            $record->end_date ?? 'N/A',
            $record->as_at_date ?? 'N/A',
            $this->resolveNumberFormat($record->total_leads ?? 0),
            $this->resolveNumberFormat($record->bad_leads ?? 0),
            $this->resolveNumberFormat($record->sale_leads ?? 0),
            $this->resolveNumberFormat($record->net_conversion ?? 0),
            $this->resolveNumberFormat($record->gross_conversion ?? 0),
        ]);

        foreach ($this->columnTotals as $index => $field) {
            // Sum only numeric columns: total_leads (3), bad_leads (4), sale_leads (5)
            $sumColumns = [4, 5, 6];
            if (is_numeric($row->get($index)) && in_array($index+1, $sumColumns)) {
                $this->columnTotals->put($index, ((float) $this->columnTotals->get($index, 0) + (float) ($row->get($index) ?? 0)));
            }
        }

        return $row->values()->toArray();
    }

    private function postDataRows($stream)
    {
        $totalsRow = $this->getEmptyRow();


        logger()->debug("postDataRows: ".print_r([
                'columnTotals' => $this->columnTotals
            ],1));

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
