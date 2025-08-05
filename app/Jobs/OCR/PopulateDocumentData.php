<?php

namespace App\Jobs\OCR;

use App\Enums\ApplicationStorageEnums;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\OCRDocumentTypeEnum;
use App\Enums\QuoteTypes;
use App\Events\OcrNotifications;
use App\Models\DocumentType;
use App\Services\Logger\LoggerService;
use App\Services\OCR\OCRService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\Skip;

class PopulateDocumentData implements ShouldQueue
{
    use Queueable;

    public $tries = 1;
    public $timeout = 100;
    public $backoff = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected QuoteTypes $quoteType,
        protected Model $quote,
        protected DocumentType $documentType,
        protected string $documentPath,
        protected string $fileMimeType,
        protected int $userId,
        protected bool $isEcom = false,
    ) {
        $this->onQueue('shared');
    }

    private function validateMimeType()
    {
        return in_array($this->fileMimeType, ['application/pdf', ...OCRService::IMAGE_MIME_TYPES]);
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        LoggerService::startQuoteLogging($this->quote, LoggerFeatureEnum::OCR);

        if (! $this->validateMimeType()) {
            LoggerService::info(self::class." - Invalid file mime type {$this->fileMimeType} for {$this->quoteType?->value} & Document Type {$this->documentType?->code}");

            return;
        }

        try {
            $isSuccess = app(OCRService::class)->process(
                $this->quoteType,
                $this->quote,
                $this->documentType,
                $this->documentPath,
                $this->fileMimeType,
                $this->userId,
                $this->isEcom,
            );

            if ($isSuccess === null) {
                LoggerService::info(self::class." - Document data population skipped for {$this->quoteType?->value} & Document Type {$this->documentType?->code} & Quote UUID {$this->quote->uuid}");

                return;
            }

            if ($isSuccess) {
                LoggerService::info(self::class." - Document data populated successfully for {$this->quoteType?->value} & Document Type {$this->documentType?->code} & Quote UUID {$this->quote->uuid}");
            } else {
                LoggerService::warning(self::class." - Document data population failed for {$this->quoteType?->value} & Document Type {$this->documentType?->code} & Quote UUID {$this->quote->uuid}");
                $errorMessage = "OCR processing failed for {$this->quoteType?->value} & Document Type {$this->documentType?->code} & Quote UUID {$this->quote->uuid}";
                LoggerService::error(self::class." - {$errorMessage}");
                $this->fail(new Exception($errorMessage));
            }
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function middleware()
    {
        $isOCREnabled = getAppStorageValueByKey(ApplicationStorageEnums::OCR_ENABLED, useCache: true) == '1';

        return [
            Skip::unless(fn () => $isOCREnabled && OCRDocumentTypeEnum::isOCREnabled($this->documentType, $this->quoteType)),
        ];
    }

    public function failed(\Throwable $exception)
    {
        LoggerService::info(self::class.'::failed - OCR processing failed - Quote UUID: '.$this->quote->uuid, extra: [
            'quote_uuid' => $this->quote->uuid,
        ]);

        // Send OCR fail notification for supported document types (skip for ecom)
        $docType = OCRDocumentTypeEnum::getDocumentType($this->documentType);
        if (! $this->isEcom && app(OCRService::class)->requiresOcrNotifications($docType)) {
            event(new OcrNotifications($this->quote, 'fail', 'OCR processing failed', null, $docType?->value, $this->userId));
        }
    }
}
