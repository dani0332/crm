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

        // Apply permission middleware
        // $this->middleware('permission:' . PermissionsEnum::VIEW_SAGE_API_LOGS);
    }

    /**
     * Display a listing of failed sage processes
     */
    public function index(Request $request): \Inertia\Response|\Inertia\ResponseFactory
    {
        try {
            // Get failed sage processes data
            $failedProcesses = $this->sageProcessesService->getFailedSageProcesses($request);
           // dd($failedProcesses->toArray());
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
            ]);
        }
    }

    public function export(Request $request)
    {
        try {
            $failedProcesses = $this->sageProcessesService->getFailedSageProcesses($request, true);
            return (new SageProcessesExport($failedProcesses))->download('sage-failed-processes.csv');
        } catch (\Exception $e) {
            LoggerService::error('SageProcessesController - export - Error: '.$e->getMessage(), extra: [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
