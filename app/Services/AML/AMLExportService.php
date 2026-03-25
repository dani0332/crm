<?php

namespace App\Services\AML;

use App\Enums\ExportTypeEnum;
use App\Exports\KycLogsExport;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AMLExportService
{
    public function __construct(
        private readonly KycLogsExport $kycLogsExport
    ) {}

    public function exportAMLLogs(Request $request): StreamedResponse|JsonResponse
    {
        $response = null;

        try {
            LoggerService::info('AML Export requested', extra: [
                'export_type' => $request->exportType ?? ExportTypeEnum::Download->value,
                'date_range' => [
                    'start' => $request->amlCreatedStartDate,
                    'end' => $request->amlCreatedEndDate,
                ],
                'user' => auth()->user()?->id ?? 'guest',
            ]);

            $reportDateRange = $this->formatDateRange($request->amlCreatedStartDate, $request->amlCreatedEndDate);
            $exportParams = $this->prepareExportParams($request, $reportDateRange);

            $response = $request->exportType === ExportTypeEnum::Email->value
                ? $this->emailExport($reportDateRange, $exportParams)
                : $this->downloadExport($reportDateRange, $exportParams);
        } catch (\InvalidArgumentException $e) {
            LoggerService::warning('AML Export validation failed', extra: [
                'error' => $e->getMessage(),
                'request_data' => $request->only(['amlCreatedStartDate', 'amlCreatedEndDate', 'exportType']),
            ]);

            $response = response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            LoggerService::error('AML Export failed unexpectedly', extra: [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $response = response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while processing the export.',
            ], 500);
        }

        return $response;
    }

    private function formatDateRange(?string $startDate, ?string $endDate): string
    {
        if (! $startDate || ! $endDate) {
            throw new \InvalidArgumentException(
                'Date range parameters are required for AML export. Both amlCreatedStartDate and amlCreatedEndDate must be provided.'
            );
        }

        return Carbon::parse($startDate)->toDateString().' - '.Carbon::parse($endDate)->toDateString();
    }

    private function prepareExportParams(Request $request, string $reportDateRange): array
    {
        return array_merge($request->all(), [
            'exportTitle' => 'AML',
            'reportDateRange' => $reportDateRange,
            'created_at_start' => $request->amlCreatedStartDate,
            'created_at_end' => $request->amlCreatedEndDate,
        ]);
    }

    private function emailExport(string $reportDateRange, array $params): JsonResponse
    {
        try {
            LoggerService::info('AML Logs email export initiated', extra: [
                'date_range' => $reportDateRange,
                'params' => $params,
            ]);

            // Return the response from emailCSV to maintain backward compatibility
            return $this->kycLogsExport->emailCSV("AML Logs {$reportDateRange}", $params);
        } catch (\Exception $e) {
            LoggerService::warning('AML Logs email export failed', extra: [
                'error' => $e->getMessage(),
                'date_range' => $reportDateRange,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate export.',
            ], 500);
        }
    }

    /**
     * Download export directly
     */
    private function downloadExport(string $reportDateRange, array $params): StreamedResponse|JsonResponse
    {
        try {
            LoggerService::info('AML Logs download export initiated', extra: [
                'date_range' => $reportDateRange,
                'params' => $params,
            ]);

            // Merge params into request so they're available during streaming
            request()->merge($params);

            return $this->kycLogsExport->download("AML Logs {$reportDateRange}");
        } catch (\Exception $e) {
            LoggerService::error('AML Logs download export failed', extra: [
                'error' => $e->getMessage(),
                'date_range' => $reportDateRange,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate download export.',
            ], 500);
        }
    }
}
