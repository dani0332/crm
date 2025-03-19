<?php

namespace App\Jobs\OCR;

use App\Enums\OCRDocumentTypeEnum;
use App\Enums\QuoteTypes;
use App\Models\DocumentType;
use App\Services\OCR\OCRService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\Skip;

class PopulateDocumentData implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
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
    ) {}

    private function validateMimeType()
    {
        return in_array($this->fileMimeType, ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg']);
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        if (! $this->validateMimeType()) {
            info(self::class." - Invalid file mime type {$this->fileMimeType} for {$this->quoteType?->value} & Document Type {$this->documentType?->code} with UUID: {$this->quote->uuid}");

            return;
        }

        $isSuccess = app(OCRService::class)->process(
            $this->quoteType,
            $this->quote,
            $this->documentType,
            $this->documentPath
        );

        if ($isSuccess === null) {
            info(self::class." - Document data population skipped for {$this->quoteType?->value} & Document Type {$this->documentType?->code} with UUID: {$this->quote->uuid}");

            return;
        }

        if ($isSuccess) {
            info(self::class." - Document data populated successfully for {$this->quoteType?->value} & Document Type {$this->documentType?->code} with UUID: {$this->quote->uuid}");
        } else {
            info(self::class." - Document data population failed for {$this->quoteType?->value} & Document Type {$this->documentType?->code} with UUID: {$this->quote->uuid}");

            if ($this->attempts() >= $this->tries) {
                $errorMessage = "Maximum attempts reached for {$this->quoteType?->value} & Document Type {$this->documentType?->code} with UUID: {$this->quote->uuid}";
                info(self::class." - {$errorMessage}");

                $this->fail(new Exception($errorMessage));
            } else {
                // Retry the job
                $this->release(now()->addMinutes(2 * $this->attempts()));
            }
        }
    }

    public function middleware()
    {
        return [
            Skip::unless(fn () => OCRDocumentTypeEnum::isOCREnabled($this->documentType, $this->quoteType)),
        ];
    }
}
