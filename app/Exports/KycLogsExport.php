<?php

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Services\AMLService;
use App\Traits\ModernCsvExportable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class KycLogsExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    public function __construct(
        private AMLService $amlService
    ) {}

    public function collection(array $requestParams = []): Collection
    {
        return $this->amlService->getAMLData(requestParams: $requestParams);
    }

    public function getQuery(array $requestParams = []): ?Builder
    {
        return $this->amlService->getAMLData(requestParams: $requestParams);
    }

    public function headings(): array
    {
        return [
            'Ref-ID',
            'AML ID',
            'Input',
            'Search type',
            'Match Found',
            'Result Found',
            'Created At',
            'AML CRM Status',
            'Final Status',
        ];
    }

    public function map($item): array
    {
        return [
            $item->uuid ?? '',
            $item->id,
            $item->input,
            $item->search_type,
            $item->match_found,
            $item->results_found,
            date(config('constants.datetime_format'), strtotime($item->created_at)),
            $item->aml_status,
            $item->decision,
        ];
    }

    /**
     * Get export metadata with AML/KYC-specific information
     */
    public function getExportMetadata(array $requestParams = []): array
    {
        return [
            'exportClass' => static::class,
            'timestamp' => now()->toISOString(),
            'parameters' => $requestParams,
            'exportType' => 'kyc_logs',
            'includesAML' => true,
            'includesPII' => true, // Contains personally identifiable information
            'dataSource' => 'aml_service',
        ];
    }
}
