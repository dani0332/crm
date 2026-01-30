<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DocumentTypeCode;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\QuoteTypes;
use App\Jobs\WatermarkDocumentsJob;
use App\Models\InsuranceProvider;
use App\Models\QuoteDocument;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RewatermarkQuoteDocumentsService
{
    private const DEFAULT_LIMIT = 500;

    /** @var array<int, int>|null */
    private static ?array $skipWatermarkProviderIdsCache = null;

    public function __construct(private QuoteDocumentService $quoteDocumentService)
    {
    }

    /**
     * Basic dispatcher: accepts quote_type_id OR quote_documentable_type + quote_documents.id list,
     * and dispatches watermark jobs for those documents.
     *
     * Any eligibility checks (doc type, etc.) are intentionally not applied here; the API consumer
     * controls which documents to target. We only apply the provider skip-watermark gate.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function handle(array $data): array
    {
        $quoteTypeId = isset($data['quote_type_id']) ? (int) $data['quote_type_id'] : null;

        $quoteModelClass = isset($data['quote_documentable_type'])
            ? (string) $data['quote_documentable_type']
            : null;

        if (! $quoteModelClass && $quoteTypeId) {
            $quoteTypeEnum = QuoteTypes::getName($quoteTypeId);
            if ($quoteTypeEnum) {
                $quoteModelClass = $quoteTypeEnum->model()::class;
            }
        }

        if (! $quoteModelClass) {
            return [
                'quote_type_id' => $quoteTypeId,
                'quote_model' => null,
                'matched_documents' => 0,
                'dispatched' => 0,
                'skipped' => [
                    'invalid_quote_type_or_quote_documentable_type' => 1,
                ],
            ];
        }

        /** @var array<int, int> $docIds */
        $docIds = $data['doc_ids'] ?? [];

        $documents = QuoteDocument::query()
            ->where('quote_documentable_type', $quoteModelClass)
            ->whereIn('id', $docIds)
            ->with(['documentType', 'quoteDocumentable'])
            ->latest('id')
            ->get();

        $dispatched = 0;
        $skippedMissingQuote = 0;
        $skippedMissingDocumentType = 0;
        $skippedProvider = 0;

        foreach ($documents as $document) {
            $quote = $document->quoteDocumentable;
            if (! $quote) {
                $skippedMissingQuote++;
                continue;
            }

            $documentType = $document->documentType;
            if (! $documentType) {
                $skippedMissingDocumentType++;
                continue;
            }

            $insuranceProviderId = $quote->insurance_provider_id ?? null;
            if ($insuranceProviderId === null && isset($quote->plan)) {
                $insuranceProviderId = $quote->plan->provider_id ?? null;
            }

            if (! $this->isWatermarkAllowedForProvider($insuranceProviderId ? (int) $insuranceProviderId : null)) {
                $skippedProvider++;
                continue;
            }

            WatermarkDocumentsJob::dispatch($document->id, $quote->uuid, $documentType->id)->afterCommit();
            // Keep `afterCommit()` for safety and consistency: it matches `QuoteDocumentService::uploadQuoteDocument()` and ensures the job won't run before a surrounding DB transaction commits (it behaves like a normal dispatch when no transaction exists).
            $dispatched++;
        }

        LoggerService::info(self::class.' - manual watermark dispatch completed', extra: [
            'quote_type_id' => $quoteTypeId,
            'quote_documentable_type' => $quoteModelClass,
            'matched_documents' => $documents->count(),
            'dispatched' => $dispatched,
        ]);

        return [
            'quote_type_id' => $quoteTypeId,
            'quote_model' => $quoteModelClass,
            'matched_documents' => $documents->count(),
            'dispatched' => $dispatched,
            'skipped' => [
                'missing_quote' => $skippedMissingQuote,
                'missing_document_type' => $skippedMissingDocumentType,
                'provider_skip_watermark' => $skippedProvider,
            ],
        ];
    }

    /**
     * Provider-only watermark gate (mirrors the provider part of QuoteDocumentService::getWatermarkProperty()).
     *
     * - Returns false when provider is configured to skip watermarking.
     * - Returns true when provider is allowed (or provider id is missing/unknown).
     */
    public function isWatermarkAllowedForProvider(?int $insuranceProviderId): bool
    {
        if (!$insuranceProviderId) {
            return true;
        }

        return !in_array($insuranceProviderId, $this->skipWatermarkProviderIds(), true);
    }

    /**
     * @return array<int, int>
     */
    private function skipWatermarkProviderIds(): array
    {
        if (self::$skipWatermarkProviderIdsCache !== null) {
            return self::$skipWatermarkProviderIdsCache;
        }

        self::$skipWatermarkProviderIdsCache = InsuranceProvider::query()
            ->where('skip_watermark', 1)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->toArray();

        return self::$skipWatermarkProviderIdsCache;
    }
}

