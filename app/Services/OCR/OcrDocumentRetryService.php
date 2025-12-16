<?php

declare(strict_types=1);

namespace App\Services\OCR;

use App\Enums\OCRDocumentTypeEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Jobs\OCR\RetryQuoteDocumentOcrJob;
use App\Models\CarQuote;
use App\Models\DocumentType;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Throwable;

class OcrDocumentRetryService
{
    private const CHUNK_SIZE = 200;

    /** @var array<string, DocumentType|null> */
    private array $documentTypeCache = [];

    public function __construct()
    {
    }

    /**
     * Re-dispatch OCR processing for car quote documents in the given date window.
     */
    public function retryCarDocuments(Carbon $startDate, Carbon $endDate): array
    {
        $documentTypeCodes = collect(OCRDocumentTypeEnum::cases())
            ->map(static fn (OCRDocumentTypeEnum $enum) => $enum->value)
            ->all();

        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->endOfDay();

        $stats = [
            'quotes_considered' => 0,
            'documents_considered' => 0,
            'documents_dispatched' => 0,
            'documents_skipped_processed' => 0,
            'documents_skipped_missing_type' => 0,
            'documents_skipped_missing_payload' => 0,
        ];

        try {
            CarQuote::query()
                ->select([
                    'id',
                    'uuid',
                    'code',
                    'quote_status_id',
                    'transaction_approved_at',
                    'policy_booking_date',
                    'insurance_provider_id',
                ])
                ->where('quote_status_id', QuoteStatusEnum::PolicyBooked)
                ->whereBetween('transaction_approved_at', [$start, $end])
                ->whereHas('documents', fn ($query) => $query->whereIn('document_type_code', $documentTypeCodes))
                ->with([
                    'documents' => function ($query) use ($documentTypeCodes) {
                        $query
                            ->whereIn('document_type_code', $documentTypeCodes)
                            ->select('id', 'quote_documentable_id', 'doc_name', 'doc_url', 'doc_mime_type', 'document_type_code', 'is_ocr_processed');
                    },
                    'payments' => function ($query) {
                        $query->latest('created_at')
                            ->take(1)
                            ->select('id', 'paymentable_id', 'paymentable_type', 'insurance_provider_id', 'created_at')
                            ->with(['insuranceProvider:id,code']);
                    },
                    'insuranceProvider:id,code',
                ])
                ->orderBy('transaction_approved_at')
                ->orderBy('id')
                ->chunk(self::CHUNK_SIZE, function (Collection $carQuotes) use (&$stats) {
                    foreach ($carQuotes as $carQuote) {
                        $stats['quotes_considered']++;

                        foreach ($carQuote->documents as $document) {
                            $stats['documents_considered']++;

                            //region Check active document Type for the given provider
                            $documentType = $this->resolveDocumentType($document->document_type_code);

                            if (! $documentType) {
                                $stats['documents_skipped_missing_type']++;
                                LoggerService::info(self::class.'::retryCarDocuments - Document type missing', [
                                    'quote_code' => $carQuote->code,
                                    'document_type_code' => $document->document_type_code,
                                ]);

                                continue;
                            }
                            //endregion

                            if (! $document->doc_url || ! $document->doc_mime_type) {
                                $stats['documents_skipped_missing_payload']++;
                                LoggerService::info(self::class.'::retryCarDocuments - Missing document payload', [
                                    'quote_code' => $carQuote->code,
                                    'document_type_code' => $document->document_type_code,
                                    'has_doc_url' => (bool) $document->doc_url,
                                    'has_doc_mime_type' => (bool) $document->doc_mime_type,
                                ]);

                                continue;
                            }

                            RetryQuoteDocumentOcrJob::dispatch($document->id, null);

                            $stats['documents_dispatched']++;
                        }
                    }
                });
        } catch (Throwable $exception) {
            LoggerService::error(self::class.'::retryCarDocuments - Failed', exception: $exception);

            throw $exception;
        }

        LoggerService::info(self::class.'::retryCarDocuments - Summary', [
            'date_range' => [$start->toDateTimeString(), $end->toDateTimeString()],
            'stats' => $stats
        ]);

        return $stats;
    }

    private function resolveDocumentType(string $documentTypeCode): ?DocumentType
    {
        if (! array_key_exists($documentTypeCode, $this->documentTypeCache)) {
            $this->documentTypeCache[$documentTypeCode] = DocumentType::query()
                ->active()
                ->byQuoteTypeId(QuoteTypes::CAR->id())
                ->where('code', $documentTypeCode)
                ->first();
        }

        return $this->documentTypeCache[$documentTypeCode];
    }
}


