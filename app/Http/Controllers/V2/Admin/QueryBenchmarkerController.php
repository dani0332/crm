<?php

namespace App\Http\Controllers\V2\Admin;

use App\Enums\ApplicationStorageEnums;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\QueryBenchmarkRequest;
use App\Services\Benchmarker\QueryBenchmarkerService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryBenchmarkerController extends Controller
{
    public function __construct(public QueryBenchmarkerService $queryBenchmarkerService)
    {
        // $this->middleware('readonly_db');

        $this->middleware('role:'.RolesEnum::Engineering);
    }

    public function show()
    {
        // CRITICAL SECURITY: Restrict access to only authorized email
        $authorizedEmail = 'ahsan.ashfaq@myalfred.com';
        if (! Auth::check() || Auth::user()->email !== $authorizedEmail) {
            abort(403, 'Access denied. This feature is restricted to authorized personnel only.');
        }

        return inertia('Admin/Benchmarker/QueryBenchmarker');
    }

    public function process(QueryBenchmarkRequest $request): JsonResponse
    {
        // CRITICAL SECURITY: Restrict access to only authorized email
        $authorizedEmail = 'ahsan.ashfaq@myalfred.com';
        if (! Auth::check() || Auth::user()->email !== $authorizedEmail) {
            return response()->json([
                'error' => true,
                'message' => 'Access denied. This feature is restricted to authorized personnel only.',
            ], 403);
        }

        Log::info('Query benchmark request', [
            'query' => $request->input('query'),
            'iterations' => $request->input('iterations', 1),
            'fetch_data' => $request->input('fetch_data', true),
            'user' => Auth::user()->email,
        ]);

        // Prevent fetching data if user doesn't have can_impersonate permission
        if ($request->input('fetch_data', true) && ! Auth::user()->can_impersonate) {
            return response()->json([
                'error' => true,
                'message' => 'You do not have permission to fetch data.',
            ]);
        }

        $timeoutThreshold = getAppStorageValueByKey(ApplicationStorageEnums::BENCHMARKING_QUERY_TIMEOUT_THRESHOLD_IN_MS, 5000);

        DB::statement("SET SESSION max_execution_time = {$timeoutThreshold}");

        $query = $request->input('query');
        $iterations = (int) $request->input('iterations', 1);
        $fetch_data = (bool) $request->input('fetch_data', true);

        try {
            $response = $this->queryBenchmarkerService->benchmark($query, $iterations, $fetch_data);

            return response()->json($response);
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
