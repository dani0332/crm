<?php

declare(strict_types=1);

namespace App\Jobs\OCR;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Models\QuoteDocument;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RetryQuoteDocumentOcrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;
    public int $backoff = 300;

    public function __construct(
        private readonly int $documentId,
        private readonly ?int $userId = null,
    ) {
        $this->onQueue('local');
    }

    public function handle(): void
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::OCR);
        LoggerService::info(self::class.' - Job started', [
            'document_id' => $this->documentId,
            'user_id' => $this->userId,
        ]);

        $document = QuoteDocument::query()
            ->select([
                'id',
                'quote_documentable_id',
                'quote_documentable_type',
                'doc_url',
                'doc_mime_type',
                'document_type_code',
                'is_ocr_processed',
            ])
            ->find($this->documentId);

        if (! $document) {
            LoggerService::info(self::class.' - Document not found, skipping', [
                'document_id' => $this->documentId,
            ]);

            return;
        }

        LoggerService::info(self::class.' - Simulating OCR API call', [
            'document_id' => $document->id,
            'document_type_code' => $document->document_type_code,
            'quote_documentable_type' => $document->quote_documentable_type,
            'quote_documentable_id' => $document->quote_documentable_id,
            'has_doc_url' => (bool) $document->doc_url,
            'has_doc_mime_type' => (bool) $document->doc_mime_type,
            'user_id' => $this->userId,
        ]);

        LoggerService::info(self::class.' - Job completed', [
            'document_id' => $document->id,
        ]);
    }
}

