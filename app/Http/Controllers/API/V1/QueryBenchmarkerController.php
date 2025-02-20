<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\QueryBenchmarkRequest;
use App\Services\Benchmarker\QueryBenchmarkerService;
use Exception;
use Illuminate\Http\JsonResponse;

class QueryBenchmarkerController extends Controller
{
    public function __construct(public QueryBenchmarkerService $queryBenchmarkerService)
    {
        $this->middleware('readonly_db');
    }

    public function process(QueryBenchmarkRequest $request): JsonResponse
    {
        $query = $request->input('query');
        $iterations = (int) $request->input('iterations', 1);

        try {
            $response = $this->queryBenchmarkerService->benchmark($query, $iterations);

            return response()->json($response);
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
