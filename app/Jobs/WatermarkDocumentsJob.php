<?php

namespace App\Jobs;

use App\Models\DocumentType;
use App\Models\QuoteDocument;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class WatermarkDocumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes
    public $tries = 3;
    public $backoff = 10;
    private $quoteDocumentId;
    private $uuid;
    private $documentTypeId;
    private $lockKey;

    /**
     * Create a new job instance.
     */
    public function __construct($quoteDocumentId, $uuid, $documentTypeId)
    {
        $this->quoteDocumentId = $quoteDocumentId;
        $this->uuid = $uuid;
        $this->documentTypeId = $documentTypeId;
        $this->lockKey = "watermark_{$quoteDocumentId}_{$uuid}_{$documentTypeId}";
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        LoggerService::startQuoteLogging($this->uuid);

        // Check if the file is already being processed
        if ($this->isFileBeingProcessed()) {
            LoggerService::info("File is already being processed. Retrying later. Document ID: {$this->quoteDocumentId}, UUID: {$this->uuid}");
            $this->release(30); // Release the job to be retried in 30 seconds

            return;
        }

        $quoteDocument = QuoteDocument::find($this->quoteDocumentId);
        $documentType = DocumentType::find($this->documentTypeId);

        // Ensure the quoteDocument and documentType exist
        if (! $quoteDocument || ! $documentType) {
            LoggerService::warning('Document or DocumentType not found. Document Id:'.$this->quoteDocumentId.' Document Type Id: '.$this->documentTypeId.' - Ref ID: '.$this->uuid);

            return;
        }

        // Check if the source file exists
        if (! $this->fileExists($quoteDocument->doc_url)) {
            LoggerService::error("Source file does not exist: {$quoteDocument->doc_url}");

            return;
        }

        try {
            // Perform watermarking based on file type
            $watermarkService = app()->make(QuoteDocumentService::class);
            $fileMimeType = $quoteDocument->doc_mime_type;
            $docName = str_replace('original_', '', $quoteDocument->doc_name);

            $extension = strtolower(pathinfo($quoteDocument->doc_name, PATHINFO_EXTENSION));

            if ($fileMimeType == 'application/pdf' || $fileMimeType == '.pdf' || $extension == 'pdf') {
                $watermarkData = $watermarkService->watermarkPdf($quoteDocument->doc_url, $docName, $this->uuid, $documentType);
            } elseif (in_array($fileMimeType, ['image/jpeg', 'image/png', 'image/jpg'])) {
                $watermarkData = $watermarkService->watermarkImage($quoteDocument->doc_url, $docName, $this->uuid, $documentType);
            } elseif (in_array($fileMimeType, ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/msword'])) {
                $watermarkData = $watermarkService->watermarkWordDocs($quoteDocument->doc_url, $docName, $this->uuid, $documentType);
            }

            // Update the document with watermark data
            if (isset($watermarkData['watermarked_doc_name']) && isset($watermarkData['watermarked_doc_url'])) {
                $quoteDocument->update([
                    'watermarked_doc_name' => $watermarkData['watermarked_doc_name'],
                    'watermarked_doc_url' => $watermarkData['watermarked_doc_url'],
                ]);
                LoggerService::info('watermark job completed for '.$this->uuid);
            }
        } catch (\Exception $e) {
            LoggerService::error("Error processing watermark for document ID: {$this->quoteDocumentId}, UUID: {$this->uuid}. Error: ".$e->getMessage());
            throw $e; // Re-throw to trigger job retry
        }
    }

    /**
     * Check if the file is already being processed
     */
    private function isFileBeingProcessed()
    {
        // Use cache to track processing status
        $cacheKey = "processing_{$this->lockKey}";
        if (cache()->has($cacheKey)) {
            return true;
        }

        // Set a processing flag with a 5-minute expiration
        cache()->put($cacheKey, true, now()->addMinutes(5));

        return false;
    }

    /**
     * Check if a file exists
     */
    private function fileExists($path)
    {
        try {
            // For local storage
            if (Storage::disk('azureIM')->exists($path)) {
                return true;
            }

            // For remote URLs
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                $headers = get_headers($path);

                return $headers && strpos($headers[0], '200') !== false;
            }

            return false;
        } catch (\Exception $e) {
            LoggerService::error("Error checking file existence: {$path}. Error: ".$e->getMessage());

            return false;
        }
    }

    public function middleware()
    {
        // Use a more specific lock key and increase the lock duration
        return [(new WithoutOverlapping($this->lockKey))->dontRelease()->expireAfter(300)];
    }
}
