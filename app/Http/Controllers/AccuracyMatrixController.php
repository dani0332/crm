<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Services\AccuracyMatrixCacheService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AccuracyMatrixController extends Controller
{
    use GenericQueriesAllLobs;

    public function __construct(
        private readonly AccuracyMatrixCacheService $accuracyMatrixService
    ) {}

    public function getMatrixStatus(Request $request, string $quoteType, int $quoteId): JsonResponse
    {

        try {
            $request->validate([
                'quote_type' => 'sometimes|string|in:home,group_medical',
            ]);

            $quoteTypeEnum = $this->mapQuoteType($quoteType);
            if (!$quoteTypeEnum) {
                return response()->json([
                    'error' => 'Invalid quote type',
                ], Response::HTTP_BAD_REQUEST);
            }

            $quote = $this->getQuoteObject($quoteType, $quoteId);
            if (!$quote) {
                return response()->json([
                    'error' => 'Quote not found',
                ], Response::HTTP_NOT_FOUND);
            }

            if (!$this->accuracyMatrixService->isEligibleQuote($quote, $quoteTypeEnum)) {
                return response()->json([
                    'show_matrix' => false,
                    'status' => 'not_eligible',
                    'message' => 'Quote not eligible for accuracy matrix validation',
                ]);
            }

            $matrixStatus = $this->accuracyMatrixService->getMatrixStatus($quoteId, $quoteTypeEnum->value);

            if (!$matrixStatus) {
                return response()->json([
                    'show_matrix' => false,
                    'status' => 'no_data',
                    'message' => 'No validation data available',
                ]);
            }

            return response()->json([
                'show_matrix' => $matrixStatus['show_matrix'],
                'status' => $matrixStatus['status'],
                'is_valid' => $matrixStatus['is_valid'],
                'tooltip' => $matrixStatus['tooltip'],
                'last_validated' => $matrixStatus['last_validated'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function mapQuoteType(string $quoteType): ?QuoteTypes
    {
        return match (strtolower($quoteType)) {
            'home' => QuoteTypes::HOME,
            'group_medical', 'groupmedical', 'medical' => QuoteTypes::GROUP_MEDICAL,
            default => null,
        };
    }
}
