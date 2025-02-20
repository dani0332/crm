<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\Benchmarker\QueryBenchmarkerService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueryBenchmarkerController extends Controller
{
    public function __construct(public QueryBenchmarkerService $queryBenchmarkerService)
    {
        $this->middleware('readonly_db');
    }

    public function process(Request $request): JsonResponse
    {
        $query = $request->input('query');
        $iterations = (int) $request->input('iterations', 1);

        if (empty($query)) {
            return response()->json([
                'error' => true,
                'message' => 'query is required.',
            ], 422);
        }

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
