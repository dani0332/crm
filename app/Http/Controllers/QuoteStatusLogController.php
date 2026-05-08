<?php

namespace App\Http\Controllers;

use App\Services\QuoteStatusLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteStatusLogController extends Controller
{
    public function index(Request $request, QuoteStatusLogService $quoteStatusLogService): JsonResponse
    {
        $validatedData = $request->validate([
            'sendUpdateId' => ['nullable', 'integer', 'min:1'],
            'quoteId' => ['required_without:sendUpdateId', 'integer', 'min:1'],
            'quoteTypeId' => ['required_without:sendUpdateId', 'integer', 'min:1'],
        ]);

        $logs = $quoteStatusLogService->getQuoteStatusLogs(
            quoteTypeId: isset($validatedData['quoteTypeId']) ? (int) $validatedData['quoteTypeId'] : null,
            quoteId: isset($validatedData['quoteId']) ? (int) $validatedData['quoteId'] : null,
            sendUpdateId: isset($validatedData['sendUpdateId']) ? (int) $validatedData['sendUpdateId'] : null
        );

        return response()->json($logs);
    }
}
