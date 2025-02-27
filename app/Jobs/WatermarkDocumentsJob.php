<?php

namespace App\Jobs;

use App\Models\DocumentType;
use App\Models\QuoteDocument;
use App\Services\QuoteDocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WatermarkDocumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes
    public $tries = 3;
    public $backoff = 10;
    private $quoteDocumentId;
    private $uuid;
    private $documentTypeId;

    /**
     * Create a new job instance.
     */
    public function __construct($quoteDocumentId, $uuid, $documentTypeId)
    {
        $this->quoteDocumentId = $quoteDocumentId;
        $this->uuid = $uuid;
        $this->documentTypeId = $documentTypeId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        info('watermark job started for '.$this->uuid.' attempt: '.$this->attempts());
        $quoteDocument = QuoteDocument::find($this->quoteDocumentId);
        $documentType = DocumentType::find($this->documentTypeId);

        // Ensure the quoteDocument and documentType exist
        if (! $quoteDocument || ! $documentType) {
            Log::error('Document or DocumentType not found. Document Id:'.$this->quoteDocumentId.' Document Type Id: '.$this->documentTypeId.' - Ref ID: '.$this->uuid);

            return;
        }

        // Perform watermarking based on file type
        $watermarkService = app()->make(QuoteDocumentService::class);
        $fileMimeType = $quoteDocument->doc_mime_type;
        $docName = str_replace('original_', '', $quoteDocument->doc_name);

        if ($fileMimeType == 'application/pdf' || $fileMimeType == '.pdf') {
            $watermarkData = $watermarkService->watermarkPdf($quoteDocument->doc_url, $docName, $this->uuid, $documentType);
        } elseif (in_array($fileMimeType, ['image/jpeg', 'image/png', 'image/jpg'])) {
            $watermarkData = $watermarkService->watermarkImage($quoteDocument->doc_url, $docName, $this->uuid, $documentType);
        } elseif (in_array($fileMimeType, ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/msword'])) {
            $watermarkData = $watermarkService->watermarkWordDocs($quoteDocument->doc_url, $docName, $this->uuid, $documentType);
        }

        // Update the document with watermark data
        $quoteDocument->update([
            'watermarked_doc_name' => $watermarkData['watermarked_doc_name'] ?? null,
            'watermarked_doc_url' => $watermarkData['watermarked_doc_url'] ?? null,
        ]);
        info('watermark job completed for '.$this->uuid);
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->quoteDocumentId.$this->uuid.$this->documentTypeId))->dontRelease()];
    }
}
