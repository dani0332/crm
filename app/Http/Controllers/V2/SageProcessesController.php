<?php

declare(strict_types=1);

namespace App\Http\Controllers\V2;

use App\Exports\SageProcessesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\SageProcessesFilterRequest;
use App\Services\Logger\LoggerService;
use App\Services\SageFailedRecordsService;
use Inertia\Response;
use Inertia\ResponseFactory;

class SageProcessesController extends Controller
{
    protected SageFailedRecordsService $sageFailedRecordsService;

    public function __construct(SageFailedRecordsService $sageFailedRecordsService)
    {
        $this->sageFailedRecordsService = $sageFailedRecordsService;
    }

    /**
     * Display a listing of failed sage processes
     */
    public function index(SageProcessesFilterRequest $request): Response|ResponseFactory
    {
        $dropDowns = $this->sageFailedRecordsService->getDropDownData();
        $filters = $request->safe()->all();

        try {
            $sageFailedRecords = $this->sageFailedRecordsService->getFailedSageRecords($request->safe());

            $response = [
                'failedProcesses' => $sageFailedRecords,
                'filters' => $filters,
                'dropdowns' => $dropDowns,
            ];

        } catch (\Throwable $e) {
            LoggerService::warning('Error fetching failed Sage failed records', extra: [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $response = [
                'failedProcesses' => [],
                'filters' => $filters,
                'dropdowns' => $dropDowns,
                'error' => 'Failed to fetch data. Please try again.',
            ];
        }

        return inertia('SageProcesses/Index', $response);
    }

    public function export(SageProcessesFilterRequest $request)
    {
        try {
            $failedProcesses = $this->sageFailedRecordsService->getFailedSageRecords($request->safe(), true);

            if ($failedProcesses->isEmpty()) {
                return response()->json([
                    'message' => 'No data available to export.',
                ], 404);
            }

            // Generate filename - ModernCsvExportable trait will append date automatically
            $filename = 'sage-failed-processes-'.date('His');

            return (new SageProcessesExport($failedProcesses))->download($filename);
        } catch (\Throwable $e) {
            LoggerService::warning('Error exporting failed Sage failed records', extra: [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Data export failed. Please try again.',
            ], 500);
        }
    }
}
