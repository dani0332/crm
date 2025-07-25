<?php

namespace App\Exports\Reports;

use App\Contracts\CsvExportableInterface;
use App\Services\Reports\SaleSummaryReportService;
use App\Traits\ModernCsvExportable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Events\AfterSheet;

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
        //        logger()->debug("collection: " . print_r(['request' => $request->all()], true));
        $data = $this->saleSummaryReportService->getReportData($request);

        return $data;
    }

    /**
     * Get the query builder instance for chunked processing
     */
    public function getQuery(array $requestParams = []): ?\Illuminate\Database\Eloquent\Builder
    {
        $request = request()->merge($requestParams);

        return $this->saleSummaryReportService->getReportQueryBuilder($request);
    }

    public function processChunkedQuery($query, array $requestParams, $stream): int
    {
        $totalRecords = 0;
        $chunkSize = 1000;

        info('processChunkedQuery Start');

        $requestParams = request()->merge($requestParams);

        $endorsementsData = $this->saleSummaryReportService->getEndorsementsData($requestParams);

        //        logger()->debug("processChunkedQuery: " . print_r([
        //
        //            '$endorsementsData' => $endorsementsData[0]
        //
        //            ], true));

        $query->chunk($chunkSize, function ($chunk) use (&$totalRecords, $requestParams, $stream, $endorsementsData) {

            $processedData = $this->saleSummaryReportService->processEndorsementsData($chunk, $endorsementsData, $requestParams);

            $this->saleSummaryReportService->formatData($processedData);

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

        //        logger()->debug('groupBy: '.$groupBy);
        //        //logger()->debug('groupBy: '.$groupBy);
        //        logger()->debug("groupBy: ".print_r([
        //            '$quote' => $quote,
        //            //'quote->{groupBy}' => $quote->{$groupBy},
        //            ], true));

        $values = [
            $quote->{$groupBy} ?? 'N/A',
        ];

        if (in_array($this->groupByColumn, ['advisor'])) {
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

        foreach ($this->columnTotals->keys() as $field) {
            $this->columnTotals->put($field, $this->columnTotals->get($field, 0) + ($numericValues->get($field) ?? 0));
        }

        return [
            ...$values,
            ...$numericValues->values()->toArray(),
        ];
    }

    //    public static function afterSheet(AfterSheet $event)
    //    {
    //        $commonColumns = ['C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'];
    //
    //        $sumCoumns = ['B', ...$commonColumns];
    //        if (in_array($event->getConcernable()->groupByColumn, ['advisor'])) {
    //            $sumCoumns = [...$commonColumns, 'N'];
    //        }
    //        self::performSum($event, $sumCoumns);
    //    }

    private function postDataRows($stream)
    {
        $totalsRow = array_fill(0, count($this->map((object) [])), '');
        $totalsRow[0] = 'Totals';
        $offset = in_array($this->groupByColumn, ['advisor']) ? 2 : 1; // Adjust for department column
        foreach ($this->columnTotals->keys() as $index => $key) {
            $totalsRow[$index + $offset] = $this->resolveNumberFormat($this->columnTotals->get($key));
        }
        fputcsv($stream, $totalsRow);
    }
}
