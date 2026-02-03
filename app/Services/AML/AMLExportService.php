<?php

namespace App\Services\AML;

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
        LoggerService::info('AML Export requested', extra: [
            'export_type' => $request->exportType ?? 'download',
            'date_range' => [
                'start' => $request->amlCreatedStartDate,
                'end' => $request->amlCreatedEndDate,
            ],
            'user' => auth()->user()?->id ?? 'guest',
        ]);

        $reportDateRange = $this->formatDateRange($request->amlCreatedStartDate, $request->amlCreatedEndDate);
        $exportParams = $this->prepareExportParams($request, $reportDateRange);

        if ($request->exportType === 'email') {
            return $this->emailExport($reportDateRange, $exportParams);
        }

        return $this->downloadExport($reportDateRange);
    }

    private function formatDateRange(?string $startDate, ?string $endDate): string
    {
        if (! $startDate || ! $endDate) {
            return Carbon::now()->format('Y-m-d') . ' - ' . Carbon::now()->format('Y-m-d');
        }

        return Carbon::parse($startDate)->toDateString().' - '.Carbon::parse($endDate)->toDateString();
    }

    private function prepareExportParams(Request $request, string $reportDateRange): array
    {
        return [
            'exportTitle' => 'AML',
            'created_at_start' => $request->amlCreatedStartDate,
            'created_at_end' => $request->amlCreatedEndDate,
            'reportDateRange' => $reportDateRange,
        ];
    }

    private function emailExport(string $reportDateRange, array $params): JsonResponse
    {
        try {
            $this->kycLogsExport->emailCSV("AML Logs {$reportDateRange}", $params);

            LoggerService::info('AML Logs email export initiated', extra: [
                'date_range' => $reportDateRange,
                'params' => $params,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Export is being processed. You will receive an email with the CSV file shortly.',
                'date_range' => $reportDateRange,
            ]);
        } catch (\Exception $e) {
            LoggerService::warning('AML Logs email export failed', extra: [
                'error' => $e->getMessage(),
                'date_range' => $reportDateRange,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to initiate export.',
            ]);
        }
    }

    /**
     * Download export directly
     */
    private function downloadExport(string $reportDateRange): StreamedResponse
    {
        LoggerService::info('AML Logs download export initiated', extra: [
            'date_range' => $reportDateRange,
        ]);

        return $this->kycLogsExport->download("AML Logs {$reportDateRange}");
    }
}
