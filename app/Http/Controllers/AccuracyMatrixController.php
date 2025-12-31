<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\QuoteTypes;
use App\Services\AccuracyMatrixService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AccuracyMatrixController extends Controller
{
    use GenericQueriesAllLobs;

    public function __construct(
        private readonly AccuracyMatrixService $accuracyMatrixService
    ) {}

    public function getMatrixStatus(string $quoteType, int $quoteId): JsonResponse
    {
        LoggerService::info('AccuracyMatrixController::getMatrixStatus - Start', [
            'quote_type' => $quoteType,
            'quote_id' => $quoteId,
        ]);

        try {
            $quoteTypeEnum = $this->mapQuoteType($quoteType);
            if (! $quoteTypeEnum) {
                LoggerService::info('AccuracyMatrixController::getMatrixStatus - Invalid quote type', [
                    'quote_type' => $quoteType,
                ]);

                return response()->json([
                    'error' => 'Invalid quote type',
                ], Response::HTTP_BAD_REQUEST);
            }

            $quote = $this->getQuoteObject($quoteType, $quoteId);
            if (! $quote) {
                LoggerService::info('AccuracyMatrixController::getMatrixStatus - Quote not found', [
                    'quote_type' => $quoteType,
                    'quote_id' => $quoteId,
                ]);

                return response()->json([
                    'error' => 'Quote not found',
                ], Response::HTTP_NOT_FOUND);
            }

            LoggerService::info('AccuracyMatrixController::getMatrixStatus - Quote found', [
                'quote_id' => $quoteId,
                'quote_type' => $quoteType,
                'quote_uuid' => $quote->uuid ?? null,
            ]);

            if (! $this->accuracyMatrixService->isEligibleQuote($quote, $quoteTypeEnum)) {
                LoggerService::info('AccuracyMatrixController::getMatrixStatus - Quote not eligible', [
                    'quote_id' => $quoteId,
                    'quote_type' => $quoteType,
                ]);

                return response()->json([
                    'show_matrix' => false,
                    'status' => 'not_eligible',
                    'message' => 'Quote not eligible for accuracy matrix validation',
                ]);
            }

            $matrixStatus = $this->accuracyMatrixService->getMatrixStatus($quoteId, $quoteTypeEnum->value);

            if (! $matrixStatus) {
                LoggerService::info('AccuracyMatrixController::getMatrixStatus - No matrix status data', [
                    'quote_id' => $quoteId,
                    'quote_type' => $quoteType,
                ]);

                return response()->json([
                    'show_matrix' => false,
                    'status' => 'no_data',
                    'message' => 'No validation data available',
                ]);
            }

            LoggerService::info('AccuracyMatrixController::getMatrixStatus - Matrix status found', [
                'quote_id' => $quoteId,
                'quote_type' => $quoteType,
                'matrix_status' => $matrixStatus,
            ]);

            return response()->json([
                'show_matrix' => $matrixStatus['show_matrix'],
                'status' => $matrixStatus['status'],
                'is_valid' => $matrixStatus['is_valid'],
                'tooltip' => $matrixStatus['tooltip'],
                'last_validated' => $matrixStatus['last_validated'],
            ]);

        } catch (\Exception $e) {
            LoggerService::error('AccuracyMatrixController::getMatrixStatus - Exception', [
                'quote_id' => $quoteId,
                'quote_type' => $quoteType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function mapQuoteType(string $quoteType): ?QuoteTypes
    {
        $mappedType = match ($quoteType) {
            QuoteTypes::HOME->value => QuoteTypes::HOME,
            QuoteTypes::BUSINESS->value => QuoteTypes::BUSINESS,
            default => null,
        };

        LoggerService::info('AccuracyMatrixController::mapQuoteType', [
            'input_quote_type' => $quoteType,
            'mapped_quote_type' => $mappedType?->value,
            'is_valid' => $mappedType !== null,
        ]);

        return $mappedType;
    }
}
