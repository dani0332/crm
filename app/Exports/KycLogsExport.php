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
        // For non-chunked processing, execute the query and return collection
        $query = $this->amlService->getAMLQueryBuilder($requestParams);

        return $this->amlService->processAMLDataFromQuery($query);
    }

    public function getQuery(array $requestParams = []): ?Builder
    {
        // For chunked processing (email exports), return the query builder
        return $this->amlService->getAMLQueryBuilder($requestParams);
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
     * Custom chunked processing for AML data exports
     * This method mirrors the exact logic from AMLService::processAMLDataFromQuery
     * but processes chunks individually to write directly to the stream
     */
    public function processChunkedQuery($query, array $requestParams, $stream): int
    {
        $totalRecords = 0;
        $chunkSize = 1000;

        $query->chunk($chunkSize, function ($chunk) use (&$totalRecords, $stream) {
            // Use the EXACT same processing logic as AMLService::processAMLDataFromQuery
            $quoteTypeGroup = $chunk->groupBy('quote_type_id');

            foreach ($quoteTypeGroup as $quoteTypeId => $quoteTypeData) {
                $quoteType = \App\Enums\QuoteTypes::getName($quoteTypeId);

                // Skip if quote type is not found
                if (! $quoteType) {
                    continue;
                }

                $nameSpace = '\\App\\Models\\';
                $model = checkPersonalQuotes(ucwords($quoteType->value)) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($quoteType->value).'Quote';

                $distinctQuoteTypeIds = $quoteTypeData->pluck('quote_request_id')->unique();
                $quoteRequestData = $model::whereIn('id', $distinctQuoteTypeIds)->select(['id', 'uuid', 'aml_status'])->get();

                foreach ($quoteRequestData as $quoteRequest) {
                    $amlData = $chunk->where('quote_type_id', $quoteTypeId)->where('quote_request_id', $quoteRequest->id);

                    // Use the same approach as AMLService - modify the chunk items
                    foreach ($amlData as $index => $value) {
                        // Find the original index in the chunk and modify it
                        $originalIndex = $chunk->search(function ($item) use ($value) {
                            return $item->id === $value->id;
                        });

                        if ($originalIndex !== false) {
                            $chunk[$originalIndex]->uuid = $quoteType->shortCode().$quoteRequest->uuid;
                            $chunk[$originalIndex]->aml_status = $quoteRequest->aml_status;
                        }
                    }
                }
            }

            // Now process ALL records in the chunk (just like the download path does)
            foreach ($chunk as $record) {
                fputcsv($stream, $this->map($record));
                $totalRecords++;
            }
        });

        return $totalRecords;
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
