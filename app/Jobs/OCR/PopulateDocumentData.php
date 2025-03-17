<?php

namespace App\Jobs\OCR;

use App\Enums\QuoteTypes;
use App\Models\DocumentType;
use App\Services\OCR\OCRService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;

class PopulateDocumentData implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected QuoteTypes|string $quoteType,
        protected Model $quote,
        protected DocumentType $documentType,
        protected string $documentPath
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        app(OCRService::class)->process(
            $this->quoteType,
            $this->quote,
            $this->documentType,
            $this->documentPath
        );
    }
}
