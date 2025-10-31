<?php

declare(strict_types=1);

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Logger\LoggerService;
use App\Services\SageProcessesService;
use App\Exports\SageProcessesExport;
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
    public function index(Request $request): \Inertia\Response|\Inertia\ResponseFactory
    {
        try {
            // Get failed sage processes data
            $failedProcesses = $this->sageProcessesService->getFailedSageProcesses($request);
            $dropdownData = $this->sageProcessesService->drowpdownData();

            return inertia('SageProcesses/Index', [
                'failedProcesses' => $failedProcesses,
                'filters' => request()->all(),
                'dropdowns' => $dropdownData,
            ]);
        } catch (\Exception $e) {
            LoggerService::error('SageProcessesController - index - Error: '.$e->getMessage(), extra: [
                'trace' => $e->getTraceAsString(),
            ]);
            return inertia('SageProcesses/Index', [
                'failedProcesses' => [],
                'insuranceProviders' => [],
                'quoteTypes' => [],
                'users' => [],
                'filters' => request()->all(),
                'error' => 'Failed to fetch data. Please try again.',
                'exception' => $e->getMessage(),
            ]);
        }
    }

    public function export(Request $request)
    {
        try {
            // Get failed sage processes with applied filters
            $failedProcesses = $this->sageProcessesService->getFailedSageProcesses($request, true);

            // Check if there's any data to export
            if (empty($failedProcesses) || (is_countable($failedProcesses) && count($failedProcesses) === 0)) {
                return response()->json([
                    'message' => 'No data available to export.'
                ], 404);
            }

            // Generate filename with timestamp
            $filename = 'sage-failed-processes-' . date('Y-m-d-His');

            return (new SageProcessesExport($failedProcesses))->download($filename);
        } catch (\Exception $e) {
            LoggerService::error('SageProcessesController - export - Error: '.$e->getMessage(), extra: [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
