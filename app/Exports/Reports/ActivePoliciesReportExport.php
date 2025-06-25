<?php

namespace App\Exports\Reports;

use App\Contracts\CsvExportableInterface;
use App\Services\Logger\LoggerService;
use App\Services\Reports\ActivePoliciesReportService;
use App\Traits\ModernCsvExportable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Events\AfterSheet;

class ActivePoliciesReportExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    protected Collection $columnTotals;

    public function __construct(
        private ActivePoliciesReportService $activePoliciesReportService,
        private array                    $requestParams
    )
    {
        request()->merge($this->requestParams);
        //        if(request()->filled('groupBy')){
        //            $this->groupByColumn = request()->groupBy;
        //            // Initialize totals for numeric columns
        //        }

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
        //        logger()->debug("collection: " . print_r(['request' => $request->all()], true));
        return $this->activePoliciesReportService->getReportData($request);
    }

    /**
     * Get the query builder instance for chunked processing
     */
    public function getQuery(array $requestParams = []): ?\Illuminate\Database\Eloquent\Builder
    {
        $request = request()->merge($requestParams);
        return $this->activePoliciesReportService->getReportQueryBuilder($request);
    }

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
        $row = collect([
            $quote->insurer ?? 'N/A',
            $quote->line_of_business ?? 'N/A',
            $this->resolveNumberFormat($quote->active_policy_count ?? 0),
            $this->resolveNumberFormat($quote->price_with_vat ?? 0),
            $this->resolveNumberFormat($quote->price_without_vat ?? 0),
        ]);

        LoggerService::endLogging();

        foreach ($this->columnTotals as $index => $field) {
            //            logger()->debug("index: ".$index);
            //            logger()->debug("field: ".$field);
            //            logger()->debug("columnTotals->get(field: ".$this->columnTotals->get($index, 0));
            //            logger()->debug("numericValues->get('total_policies: ".$numericValues->get('total_policies'));

            //            logger()->debug("field: ".print_r([
            //                //'$field' => $field,
            //                'total_policies' => $numericValues->get('total_policies'),
            //                ], true));

            $sumColumns = [3,4,5];

            if (is_numeric($row->get($index)) && in_array($index, $sumColumns)) {
                $this->columnTotals->put($index, ((float) $this->columnTotals->get($index, 0) + (float) ($row->get($index) ?? 0)));
            }
        }
        return $row->values()->toArray();
    }

//    public static function afterSheet(AfterSheet $event)
//    {
//        self::performSum($event, ['C', 'D', 'E']);
//    }

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
