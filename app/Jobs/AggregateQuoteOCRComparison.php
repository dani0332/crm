<?php

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\OCRDocumentTypeEnum;
use App\Enums\QuoteTypes;
use App\Models\CarQuote;
use App\Models\LeadOcrDataComparison;
use App\Models\OCRResponseData;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AggregateQuoteOCRComparison implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 2;

    public function __construct(private int $quoteId) {}

    public function handle(): void
    {
        $quote = CarQuote::with(['personalQuote'])->find($this->quoteId);

        if (! $quote || ! $quote->personalQuote) {
            LoggerService::info(self::class.' - Quote or PersonalQuote not found', [
                'quote_id' => $this->quoteId,
            ]);

            return;
        }

        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::LEAD_OCR_DATA_COMPARISON);

        LoggerService::info(self::class.' - Starting aggregation', [
            'quote_id' => $quote->id,
            'quote_uuid' => $quote->uuid,
        ]);

        $documentResults = DB::table('temp_ocr_document_results')
            ->where('quote_id', $this->quoteId)
            ->get();

        if ($documentResults->isEmpty()) {
            LoggerService::warning(self::class.' - No document results found for aggregation', [
                'quote_id' => $this->quoteId,
            ]);

            return;
        }

        $leadDataStructure = [];
        $ocrDataStructure = [];
        $comparisonStructure = [];
        $ocrResponseStructure = [];

        foreach ($documentResults as $result) {
            $docType = $result->doc_type;

            $leadDataStructure[$docType] = json_decode($result->lead_data, true) ?? [];
            $ocrDataStructure[$docType] = json_decode($result->ocr_data, true) ?? [];
            $ocrResponseStructure[$docType] = json_decode($result->ocr_response, true) ?? [];

            if (empty($leadDataStructure[$docType])) {
                LoggerService::warning(self::class.' - Invalid or empty lead data for document, skipping comparison', [
                    'quote_id' => $quote->id,
                    'document_id' => $result->document_id,
                    'doc_type' => $docType,
                ]);

                continue;
            }

            $matchCount = 0;
            $totalFields = count($leadDataStructure[$docType]);

            // Special handling for Tax Invoice: tax_invoice_number and insurer_tax_number
            if ($docType === OCRDocumentTypeEnum::TAX_INVOICE->value) {
                $hasTaxInvoiceNumber = isset($leadDataStructure[$docType]['tax_invoice_number']);
                $hasInsurerTaxNumber = isset($leadDataStructure[$docType]['insurer_tax_number']);

                // If both tax_invoice_number and insurer_tax_number exist, count them as 1 field
                if ($hasTaxInvoiceNumber && $hasInsurerTaxNumber) {
                    $totalFields--; // Reduce by 1 since we're treating both as 1 logical field
                }

                // Handle tax invoice number comparison (if either field exists)
                if ($hasTaxInvoiceNumber || $hasInsurerTaxNumber) {
                    $ocrTaxInvoiceNumber = $ocrDataStructure[$docType]['tax_invoice_number'] ?? null;
                    $leadTaxInvoiceNumber = $leadDataStructure[$docType]['tax_invoice_number'] ?? null;
                    $leadInsurerTaxNumber = $leadDataStructure[$docType]['insurer_tax_number'] ?? null;

                    // Check if OCR's tax_invoice_number matches EITHER lead's tax_invoice_number OR insurer_tax_number
                    $taxInvoiceMatch = $this->valuesMatch($leadTaxInvoiceNumber, $ocrTaxInvoiceNumber)
                        || $this->valuesMatch($leadInsurerTaxNumber, $ocrTaxInvoiceNumber);

                    if ($taxInvoiceMatch) {
                        $matchCount++;
                    }
                }

                // Process other fields normally, skipping tax_invoice_number and insurer_tax_number
                foreach ($leadDataStructure[$docType] as $key => $leadValue) {
                    // Skip both tax invoice fields - already handled above
                    if ($key === 'tax_invoice_number' || $key === 'insurer_tax_number') {
                        continue;
                    }

                    // Normal comparison for other fields
                    if (! array_key_exists($key, $ocrDataStructure[$docType])) {
                        continue;
                    }

                    $ocrValue = $ocrDataStructure[$docType][$key];

                    if ($this->valuesMatch($leadValue, $ocrValue)) {
                        $matchCount++;
                    }
                }
            } else {
                // Normal comparison for all other document types
                foreach ($leadDataStructure[$docType] as $key => $leadValue) {
                    if (! array_key_exists($key, $ocrDataStructure[$docType])) {
                        continue;
                    }

                    $ocrValue = $ocrDataStructure[$docType][$key];

                    if ($this->valuesMatch($leadValue, $ocrValue)) {
                        $matchCount++;
                    }
                }
            }

            $comparisonStructure[$docType] = [
                'count' => $totalFields,
                'match_count' => $matchCount,
                'accuracy' => $totalFields > 0
                    ? number_format(($matchCount / $totalFields) * 100, 2)
                    : 0,
            ];

            LoggerService::info(self::class.' - Document comparison calculated', [
                'quote_id' => $quote->id,
                'doc_type' => $docType,
                'total_fields' => $totalFields,
                'matched_fields' => $matchCount,
                'accuracy' => $comparisonStructure[$docType]['accuracy'].'%',
            ]);
        }

        if (empty($comparisonStructure)) {
            LoggerService::warning(self::class.' - No valid documents to compare, all data was corrupted or empty', [
                'quote_id' => $quote->id,
            ]);

            return;
        }

        $totalMatches = array_sum(array_column($comparisonStructure, 'match_count'));
        $totalFields = array_sum(array_column($comparisonStructure, 'count'));
        $comparisonScore = $totalFields > 0
            ? number_format(($totalMatches / $totalFields) * 100, 2)
            : 0;

        LoggerService::info(self::class.' - Overall comparison score calculated', [
            'quote_id' => $quote->id,
            'total_fields' => $totalFields,
            'total_matches' => $totalMatches,
            'comparison_score' => $comparisonScore.'%',
        ]);

        DB::transaction(function () use ($quote, $leadDataStructure, $ocrDataStructure, $comparisonStructure, $comparisonScore, $ocrResponseStructure) {

            $comparisonRecord = LeadOcrDataComparison::updateOrCreate(
                ['uuid' => $quote->uuid],
                [
                    'quoteable_id' => $quote->id,
                    'quoteable_type' => QuoteTypes::CAR->modelClass(),
                    'lead_data' => json_encode($leadDataStructure),
                    'compairson_data' => json_encode($comparisonStructure),
                    'comparison_score' => $comparisonScore,
                    'timestamp' => now()->valueOf(),
                ]
            );

            OCRResponseData::updateOrCreate(
                [
                    'quoteable_id' => $quote->id,
                    'quoteable_type' => QuoteTypes::CAR->modelClass(),
                ],
                [
                    'ocr_response' => json_encode($ocrResponseStructure),
                    'ocr_data' => json_encode($ocrDataStructure),
                ]
            );

            $quote->personalQuote->update([
                'lead_ocr_comparison_processed' => true,
            ]);

            DB::table('temp_ocr_document_results')
                ->where('quote_id', $quote->id)
                ->delete();

            LoggerService::info(self::class.' - Data saved and intermediate results cleaned up', [
                'quote_id' => $quote->id,
                'quote_uuid' => $quote->uuid,
                'record_action' => $comparisonRecord->wasRecentlyCreated ? 'created' : 'updated',
            ]);
        });

        LoggerService::info(self::class.' - Aggregation completed successfully', [
            'quote_id' => $quote->id,
            'quote_uuid' => $quote->uuid,
            'comparison_score' => $comparisonScore.'%',
            'documents_processed' => $documentResults->count(),
        ]);

        // Clear aggregation cache to allow recalculation
        Cache::forget('ocr_aggregation_dispatched_'.$this->quoteId);

        LoggerService::info(self::class.' - Aggregation cache cleared', [
            'quote_id' => $this->quoteId,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        // Clear aggregation cache to allow retry
        Cache::forget('ocr_aggregation_dispatched_'.$this->quoteId);

        LoggerService::error(self::class.' - Aggregation job failed', extra: [
            'quote_id' => $this->quoteId,
            'attempts' => $this->attempts(),
            'exception' => $exception->getMessage(),
            'exception_trace' => $exception->getTraceAsString(),
            'exception_code' => $exception->getCode(),
            'exception_file' => $exception->getFile(),
            'exception_line' => $exception->getLine(),
        ]);
    }

    private function valuesMatch($leadValue, $ocrValue): bool
    {
        if ($leadValue === $ocrValue) {
            return true;
        }

        if ($leadValue === null || $ocrValue === null) {
            return $leadValue === $ocrValue;
        }

        $leadStr = (string) $leadValue;
        $ocrStr = (string) $ocrValue;

        return strtolower(trim($leadStr)) === strtolower(trim($ocrStr));
    }
}
