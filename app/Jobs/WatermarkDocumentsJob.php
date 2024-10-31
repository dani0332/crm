<?php

namespace App\Jobs;

use App\Models\DocumentType;
use App\Models\QuoteDocument;
use Illuminate\Bus\Queueable;
use App\Services\QuoteDocumentService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class WatermarkDocumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $quoteDocumentId, $tempFilePath, $data, $documentTypeId;

    /**
     * Create a new job instance.
     */
    public function __construct($quoteDocumentId, $tempFilePath, $data, $documentTypeId)
    {
        $this->quoteDocumentId = $quoteDocumentId;
        $this->tempFilePath = $tempFilePath;
        $this->data = $data;
        $this->documentTypeId = $documentTypeId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        info("watermark job started");
        $quoteDocument = QuoteDocument::find($this->quoteDocumentId);
        $documentType = DocumentType::find($this->documentTypeId);

        // Ensure the quoteDocument and documentType exist
        if (!$quoteDocument || !$documentType) {
            Log::error('Document or DocumentType not found.');
            return;
        }

        // Get the file content
        $fileContent = Storage::disk('temp')->get($this->tempFilePath);

        info(file_exists(storage_path('temp/'. $this->tempFilePath)) ? 'After Job File exists' : 'After Job File not exists');

        // Perform watermarking based on file type
        $watermarkService = app()->make(QuoteDocumentService::class);
        $fileMimeType = $quoteDocument->doc_mime_type;
        $docName = str_replace('original_', '', $quoteDocument->doc_name);

        if ($fileMimeType == 'application/pdf' || $fileMimeType == '.pdf') {
            $watermarkData = $watermarkService->watermarkPdf($this->tempFilePath, $docName, $this->data, $quoteDocument->quote, $documentType, $quoteDocument->original_name, $fileMimeType);
        } elseif (in_array($fileMimeType, ['image/jpeg', 'image/png', 'image/jpg'])) {
            $watermarkData = $watermarkService->watermarkImage($fileContent, $docName, $this->data, $quoteDocument->quote, $documentType, $quoteDocument->original_name, $fileMimeType);
        } elseif (in_array($fileMimeType, ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/msword'])) {
            $watermarkData = $watermarkService->watermarkWordDocs($fileContent, $docName, $this->data, $quoteDocument->quote, $documentType, $quoteDocument->original_name, $fileMimeType);
        }

        // Update the document with watermark data
        $quoteDocument->update([
            'watermarked_doc_name' => $watermarkData['watermarked_doc_name'] ?? null,
            'watermarked_doc_url' => $watermarkData['watermarked_doc_url'] ?? null,
        ]);
        info("watermark job completed");
    }
}
