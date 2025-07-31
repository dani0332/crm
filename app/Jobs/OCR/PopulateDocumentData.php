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
            info(self::class." - Invalid file mime type {$this->fileMimeType} for {$this->quoteType?->value} & Document Type {$this->documentType?->code}");

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
                info(self::class." - Document data population skipped for {$this->quoteType?->value} & Document Type {$this->documentType?->code}");

                return;
            }

            if ($isSuccess) {
                info(self::class." - Document data populated successfully for {$this->quoteType?->value} & Document Type {$this->documentType?->code}");
            } else {
                info(self::class." - Document data population failed for {$this->quoteType?->value} & Document Type {$this->documentType?->code}");
                if ($this->attempts() >= $this->tries) {
                    $errorMessage = "Maximum attempts reached for {$this->quoteType?->value} & Document Type {$this->documentType?->code}";
                    info(self::class." - {$errorMessage}");
                    $this->fail(new Exception($errorMessage));
                } else {
                    // Retry the job
                    $this->release(now()->addMinutes(2 * $this->attempts()));
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }

        LoggerService::endLogging();
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
        // Send OCR fail notification for supported document types (skip for ecom)
        $docType = OCRDocumentTypeEnum::getDocumentType($this->documentType);
        if (!$this->isEcom && app(OCRService::class)->requiresOcrNotifications($docType)) {
            event(new OcrNotifications($this->quote, 'fail', 'OCR processing failed', null, $docType?->value, $this->userId));
        }
    }
}
