<?php

namespace App\Jobs\OCR;

use App\Enums\ApplicationStorageEnums;
use App\Enums\OCRDocumentTypeEnum;
use App\Enums\QuoteTypes;
use App\Events\OcrNotifications;
use App\Models\DocumentType;
use App\Services\Logger\LoggerService;
use App\Services\OCR\OCRService;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\Skip;

class PopulateDocumentData implements ShouldQueue
{
    use Batchable, Queueable;

    public $tries = 1;
    public $timeout = 100;
    public $backoff = 300;
    protected bool $isEcom = false;

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
        bool $isEcom = false,
        protected bool $isSendUpdateEligibleForOCR = false,
        protected int $quoteDocumentId,
    ) {
        $this->isEcom = $isEcom;
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
        if (! $this->validateMimeType()) {
            $errorMessage = "Invalid file mime type {$this->fileMimeType} for {$this->quoteType?->value} & Document Type {$this->documentType?->code}";

            LoggerService::info(self::class.' - '.$errorMessage, extra: [
                'quote_code' => $this->quote->code ?? null,
                'document_type_code' => $this->documentType?->code ?? null,
                'user_id' => $this->userId,
            ]);

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
                $this->isSendUpdateEligibleForOCR,
                $this->quoteDocumentId
            );

            if ($isSuccess === null) {
                LoggerService::info(self::class." - Document data population skipped for {$this->quoteType?->value} & Document Type {$this->documentType?->code}");

                return;
            }

            if ($isSuccess) {
                LoggerService::info(self::class." - Document data populated successfully for {$this->quoteType?->value} & Document Type {$this->documentType?->code}");
            } else {
                $baseLogData = [
                    'file_mime_type' => $this->fileMimeType,
                    'user_id' => $this->userId,
                    'attempts' => $this->attempts(),
                    'max_tries' => $this->tries,
                ];

                LoggerService::info(self::class." - Document data population failed for {$this->quoteType?->value} & Document Type {$this->documentType?->code}", extra: $baseLogData);

                if ($this->attempts() >= $this->tries) {
                    $errorMessage = "Maximum attempts reached for {$this->quoteType?->value} & Document Type {$this->documentType?->code}";

                    LoggerService::info(self::class.' - '.$errorMessage, extra: array_merge($baseLogData, [
                        'failure_reason' => 'max_attempts_reached',
                        'final_attempt' => true,
                    ]));

                    $this->fail(new Exception($errorMessage));
                } else {
                    $retryDelay = 2 * $this->attempts();

                    LoggerService::warning(self::class." - Retrying OCR job for {$this->quoteType?->value} & Document Type {$this->documentType?->code}", extra: array_merge($baseLogData, [
                        'retry_delay_minutes' => $retryDelay,
                        'next_attempt' => $this->attempts() + 1,
                    ]));

                    // Retry the job
                    $this->release(now()->addMinutes($retryDelay));
                }
            }
        } catch (\Exception $e) {
            LoggerService::info(self::class.' - OCR processing failed with unexpected exception', extra: [
                'quote_type' => $this->quoteType?->value ?? null,
                'quote_code' => $this->quote->code ?? null,
                'document_type_code' => $this->documentType?->code ?? null,
                'file_mime_type' => $this->fileMimeType,
                'user_id' => $this->userId,
                'attempts' => $this->attempts(),
                'max_tries' => $this->tries,
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        }

    }

    public function middleware()
    {
        $isOCREnabled = getAppStorageValueByKey(ApplicationStorageEnums::OCR_ENABLED, useCache: true) == '1';

        $docType = OCRDocumentTypeEnum::getDocumentType($this->documentType);
        $isOCRCustomerJourneyEnabled = getAppStorageValueByKey(ApplicationStorageEnums::OCR_CUSTOMER_JOURNEY_ENABLED, useCache: true) == '1';

        $isCustomerJourneyDoc = in_array($docType, [
            OCRDocumentTypeEnum::ID_CARD,
            OCRDocumentTypeEnum::REGISTRATION_CERTIFICATE,
            OCRDocumentTypeEnum::DRIVING_LICENSE,
        ]);

        $isOCRDocEnabled = OCRDocumentTypeEnum::isOCREnabled($this->documentType, $this->quoteType);
        $willRun = $isOCREnabled && $isOCRDocEnabled && (! $isCustomerJourneyDoc || $isOCRCustomerJourneyEnabled);

        LoggerService::info(self::class.'::middleware - OCR Job Middleware Check', [
            'quote_type' => $this->quoteType->value,
            'document_type' => $this->documentType->code,
            'doc_type_enum' => $docType?->value,
            'is_ocr_enabled' => $isOCREnabled,
            'is_ocr_doc_enabled' => $isOCRDocEnabled,
            'is_customer_journey_doc' => $isCustomerJourneyDoc,
            'is_customer_journey_enabled' => $isOCRCustomerJourneyEnabled,
            'will_run' => $willRun,
            'decision' => $willRun ? 'Job will execute' : 'Job will be skipped',
        ]);

        return [
            Skip::unless(fn () => $willRun),
        ];
    }

    public function failed(\Throwable $exception)
    {
        LoggerService::info(self::class.' - OCR job failed permanently', extra: [
            'quote_type' => $this->quoteType?->value ?? null,
            'quote_code' => $this->quote->code ?? null,
            'document_type_code' => $this->documentType?->code ?? null,
            'file_mime_type' => $this->fileMimeType,
            'user_id' => $this->userId ?? null,
            'total_attempts' => $this->attempts(),
            'max_tries' => $this->tries,
            'failure_reason' => 'job_failed_permanently',
            'queue_name' => $this->queue ?? 'default',
            'exception' => $exception->getMessage(),
        ]);

        // Send OCR fail notification for supported document types
        $docType = OCRDocumentTypeEnum::getDocumentType($this->documentType);

        if (app(OCRService::class)->requiresOcrNotifications($docType)) {
            try {
                event(new OcrNotifications($this->quote, 'fail', 'OCR processing failed', null, $docType?->value, $this->userId));

                LoggerService::info(self::class.' - OCR failure notification sent successfully', extra: [
                    'quote_code' => $this->quote?->code ?? null,
                    'document_type' => $docType?->value,
                    'user_id' => $this->userId,
                ]);
            } catch (\Exception $notificationException) {
                LoggerService::info(self::class.' - Failed to send OCR failure notification', extra: [
                    'quote_code' => $this->quote?->code ?? null,
                    'document_type' => $docType?->value ?? null,
                    'user_id' => $this->userId,
                    'exception' => $notificationException->getMessage(),
                ]);
            }
        } else {
            LoggerService::info(self::class.' - OCR failure notification not required for this document type', extra: [
                'quote_code' => $this->quote?->code ?? null,
                'document_type' => $docType?->value,
            ]);
        }
    }
}
