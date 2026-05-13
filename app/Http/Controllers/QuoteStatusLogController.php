<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\QuoteStatusLogIndexRequest;
use App\Services\QuoteStatusLogService;
use Illuminate\Http\JsonResponse;

class QuoteStatusLogController extends Controller
{
    public function index(QuoteStatusLogIndexRequest $request, QuoteStatusLogService $quoteStatusLogService): JsonResponse
    {
        $validatedData = $request->validated();

        $logs = $quoteStatusLogService->getQuoteStatusLogs(
            (int) $validatedData['quoteTypeId'],
            (int) $validatedData['quoteId']
        );

        return response()->json($logs);
    }
}
