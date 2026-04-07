<?php

namespace App\Exports\Reports;

use App\Contracts\CsvExportableInterface;
use App\Services\Reports\ConversionOptimizationReportService;
use App\Traits\ModernCsvExportable;
use Illuminate\Support\Collection;

class ConversionOptimizationReportExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    public function __construct(
        private ConversionOptimizationReportService $conversionOptimizationReportService,
        private array $requestParams
    ) {
        request()->merge($this->requestParams);
    }

    public function collection(array $requestParams = []): Collection
    {
        $request = request()->merge($requestParams);

        return collect($this->conversionOptimizationReportService->getReportData($request));
    }

    public function headings(): array
    {
        return [
            'Batch Number',
            'Start Date',
            'End Date',            'Advisor Name',
            'Total Leads',
            'Sale Leads',
            'Conversion',
            'Ranking',
            'Team Average',
            'Expected Sales',
            'Required Sales',
            'New Conversion %',
            'Cap Limit',
        ];
    }

    public function map($record): array
    {
        return [
            $record->batch_name ?? 'N/A',
            $record->start_date ?? 'N/A',
            $record->end_date ?? 'N/A',
            $record->advisor_name ?? 'N/A',
            $this->resolveNumberFormat($record->total_leads ?? 0),
            $this->resolveNumberFormat($record->sale_leads ?? 0),
            $this->resolveNumberFormat($record->conversion ?? 0),
            $this->resolveNumberFormat($record->ranking ?? ''),
            $this->resolveNumberFormat($record->team_average ?? ''),
            $this->resolveNumberFormat($record->expected_sales ?? ''),
            $this->resolveNumberFormat($record->required_sales ?? ''),
            $this->resolveNumberFormat($record->new_conversion ?? ''),
            $this->resolveNumberFormat($record->cap_limit ?? ''),
        ];
    }
}
