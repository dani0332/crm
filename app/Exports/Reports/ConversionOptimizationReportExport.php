<?php

namespace App\Exports\Reports;

use App\Contracts\CsvExportableInterface;
use App\Services\Logger\LoggerService;
use App\Services\Reports\ConversionOptimizationReportService;
use App\Traits\ModernCsvExportable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ConversionOptimizationReportExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    private bool $footerRowsAlreadyWritten = false;
    private float $exportSumTotalLeads = 0.0;
    private float $exportSumSaleLeads = 0.0;
    private ?float $exportTeamAverage = null;

    public function __construct(
        private ConversionOptimizationReportService $conversionOptimizationReportService,
        private array $requestParams
    ) {
        $this->footerRowsAlreadyWritten = false;
        $this->exportSumTotalLeads = 0.0;
        $this->exportSumSaleLeads = 0.0;
        $this->exportTeamAverage = null;
    }

    /**
     * Prevent merged export filters from leaking to later artisan tasks in the same PHP process.
     */
    private function resetBoundRequestSingleton(): void
    {
        app()->instance('request', Request::create('/'));
    }

    public function collection(array $requestParams = []): Collection
    {
        $request = request()->merge($requestParams);

        return collect($this->conversionOptimizationReportService->getReportData($request));
    }

    public function getQuery(array $requestParams = []): Builder|\Illuminate\Database\Query\Builder|null
    {
        $request = request()->merge($requestParams);

        return $this->conversionOptimizationReportService->getReportQueryBuilder($request);
    }

    public function processChunkedQuery($query, array $requestParams, $stream): int
    {
        LoggerService::info('ConversionOptimizationReportExport processChunkedQuery Start');

        $this->exportSumTotalLeads = 0.0;
        $this->exportSumSaleLeads = 0.0;
        $this->exportTeamAverage = null;

        $request = request()->merge($requestParams);
        $chunkSize = 1000;
        $accumulated = collect();

        $query->chunk($chunkSize, function ($chunk) use (&$accumulated) {
            $accumulated = $accumulated->merge($chunk);
        });

        $mapped = $this->conversionOptimizationReportService->mapAdvisorConversionQueryResults($accumulated);

        if ($mapped->isEmpty()) {
            $this->postDataRows($stream);

            return 0;
        }

        $processed = $this->conversionOptimizationReportService->applyPostQueryCalculations(
            $mapped,
            (array) $request->all()
        );

        $totalRecords = 0;
        $flushInterval = 5000;

        foreach ($processed as $record) {
            if ($this->exportTeamAverage === null && $record->team_average !== null && $record->team_average !== '') {
                $this->exportTeamAverage = (float) $record->team_average;
            }

            $this->exportSumTotalLeads += (float) ($record->total_leads ?? 0);
            $this->exportSumSaleLeads += (float) ($record->sale_leads ?? 0);
            fputcsv($stream, $this->map($record));
            $totalRecords++;

            if ($totalRecords % $flushInterval === 0) {
                fflush($stream);
                gc_collect_cycles();
            }
        }

        $this->postDataRows($stream);

        return $totalRecords;
    }

    /**
     * Trailing CSV rows after body (also invoked at end of {@see processChunkedQuery()} for email exports).
     * Guarded so {@see ModernCsvExportable::download()} does not duplicate footers when it calls this again.
     */
    public function postDataRows($stream): void
    {
        if ($this->footerRowsAlreadyWritten) {
            return;
        }

        try {
            $this->footerRowsAlreadyWritten = true;

            if ($this->exportSumTotalLeads <= 0 && $this->exportSumSaleLeads <= 0) {
                return;
            }

            $teamAverageCell = $this->exportTeamAverage !== null
                ? $this->resolveNumberFormat($this->exportTeamAverage)
                : '';

            fputcsv($stream, [
                'Totals',
                $this->resolveNumberFormat($this->exportSumTotalLeads),
                $this->resolveNumberFormat($this->exportSumSaleLeads),
                $teamAverageCell,
                '',
                '',
                '',
                '',
                '',
                '',
            ]);
        } finally {
            $this->resetBoundRequestSingleton();
        }
    }

    public function headings(): array
    {
        return [
            'Advisor Name',
            'Total Leads',
            'Sale Leads',
            'Conversion',
            'Ranking',
            'Expected Sales',
            'Required Sales',
            'New Conversion %',
            'Current Cap',
            'Suggested Cap',
        ];
    }

    public function map($record): array
    {
        return [
            $record->advisor_name ?? 'N/A',
            $this->resolveNumberFormat($record->total_leads ?? 0),
            $this->resolveNumberFormat($record->sale_leads ?? 0),
            $this->resolveNumberFormat($record->conversion ?? 0),
            $this->resolveNumberFormat($record->ranking ?? ''),
            $this->resolveNumberFormat($record->expected_sales ?? ''),
            $this->resolveNumberFormat($record->required_sales ?? ''),
            $this->resolveNumberFormat($record->new_conversion ?? ''),
            $this->resolveNumberFormat($record->current_cap ?? ''),
            $this->resolveNumberFormat($record->suggested_cap ?? ''),
        ];
    }
}
