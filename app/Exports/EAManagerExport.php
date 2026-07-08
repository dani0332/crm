<?php

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Services\EAManagerService;
use App\Traits\ModernCsvExportable;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EAManagerExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    public function __construct(private EAManagerService $service) {}

    public function collection(array $requestParams = []): Collection
    {
        return $this->service->getLeads($requestParams);
    }

    public function getQuery(array $requestParams = []): null
    {
        return null;
    }

    public function headings(): array
    {
        return [
            'REF ID',
            'CREATED DATE',
            'LOB',
            'EA MODEL',
            'LEAD STATUS',
            'LEAD APPROVAL STATUS',
            'LEAD GENERATOR',
            'ADVISOR',
            'EXPERT ADVISOR',
        ];
    }

    public function map($lead): array
    {
        return [
            $lead['code'] ?? '',
            isset($lead['created_at']) ? Carbon::parse($lead['created_at'])->format('Y-m-d') : '',
            $lead['quote_type'] ?? '',
            $lead['ea_model'] ?? '',
            $lead['quote_status_text'] ?? '',
            ucfirst($lead['ea_status'] ?? ''),
            $lead['lead_generator']['name'] ?? '',
            $lead['advisor']['name'] ?? '',
            $lead['expert_advisor']['name'] ?? '',
        ];
    }

    public function getExportMetadata(array $requestParams = []): array
    {
        return [
            'exportClass' => static::class,
            'timestamp' => now()->toISOString(),
            'parameters' => $requestParams,
            'exportType' => 'ea_manager_leads',
        ];
    }
}
