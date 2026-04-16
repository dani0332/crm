<?php

declare(strict_types=1);

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Exports\SageProcessesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\SageProcessesFilterRequest;
use App\Services\Logger\LoggerService;
use App\Services\SageProcessesService;
use Inertia\Response;
use Inertia\ResponseFactory;

/**
 * Controller for managing failed sage processes
 *
 * This controller handles the listing and export of failed sage processes
 * along with their related quote/send_update entries and sage api logs.
 */
class SageProcessesController extends Controller
{
    protected SageProcessesService $sageProcessesService;

    /**
     * Constructor
     */
    public function __construct(SageProcessesService $sageProcessesService)
    {
        $this->sageProcessesService = $sageProcessesService;

        $this->middleware('permission:'.PermissionsEnum::SAGE_PROCESS_ISSUE_MANAGEMENT, ['only' => ['index', 'export']]);
    }

    /**
     * Display a listing of failed sage processes
     */
    public function index(SageProcessesFilterRequest $request): Response|ResponseFactory
    {

        $dropdownData = $this->sageProcessesService->dropdownData();

        try {
            $failedProcesses = $this->sageProcessesService->getFailedSageProcesses($request->safe());

            $response = [
                'failedProcesses' => $failedProcesses,
                'filters' => request()->all(),
                'dropdowns' => $dropdownData,
            ];

        } catch (\Exception $e) {
            LoggerService::error('Error fetching failed Sage processes: '.$e->getMessage(), extra: [
                'trace' => $e->getTraceAsString(),
            ]);
            $response = [
                'failedProcesses' => [],
                'filters' => request()->all(),
                'dropdowns' => $dropdownData,
                'error' => 'Failed to fetch data. Please try again.',
            ];
        }

        return inertia('SageProcesses/Index', $response);
    }

    public function export(SageProcessesFilterRequest $request)
    {
        try {
            // Get failed sage processes with applied filters
            $failedProcesses = $this->sageProcessesService->getFailedSageProcesses($request->safe(), true);

            // Check if there's any data to export
            if ($failedProcesses->isEmpty()) {
                return response()->json([
                    'message' => 'No data available to export.',
                ], 404);
            }

            // Generate filename - ModernCsvExportable trait will append date automatically
            $filename = 'sage-failed-processes-'.date('His');

            return (new SageProcessesExport($failedProcesses))->download($filename);
        } catch (\Exception $e) {
            LoggerService::error('SageProcessesController - export - Error: '.$e->getMessage(), extra: [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Data export failed. Please try again.',
            ], 500);
        }
    }
}
