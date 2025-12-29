<?php

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
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
use Illuminate\Support\Facades\DB;

class AggregateQuoteOCRComparison implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 2;

    public function __construct(private int $quoteId)
    {
    }

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

            $leadDataStructure[$docType] = json_decode($result->lead_data, true);
            $ocrDataStructure[$docType] = json_decode($result->ocr_data, true);
            $ocrResponseStructure[$docType] = json_decode($result->ocr_response, true);

            $matchCount = 0;
            $totalFields = count($leadDataStructure[$docType]);

            foreach ($leadDataStructure[$docType] as $key => $value) {
                if (isset($ocrDataStructure[$docType][$key]) &&
                    $leadDataStructure[$docType][$key] === $ocrDataStructure[$docType][$key]) {
                    $matchCount++;
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
    }

    public function failed(\Throwable $exception): void
    {
        LoggerService::warning(self::class.' - Aggregation job failed', [
            'quote_id' => $this->quoteId,
        ]);
    }
}
