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
            'quoteId' => ['required', 'integer', 'min:1'],
            'quoteTypeId' => ['required', 'integer', 'min:1'],
        ]);

        $logs = $quoteStatusLogService->getQuoteStatusLogs(
            $validatedData['quoteTypeId'],
            $validatedData['quoteId']
        );

        return response()->json($logs);
    }
}
