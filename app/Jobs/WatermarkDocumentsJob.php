<?php

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Models\DocumentType;
use App\Models\QuoteDocument;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
use App\Traits\ChecksAzureFileExistence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use League\Flysystem\UnableToCheckExistence;
use Throwable;

class WatermarkDocumentsJob implements ShouldQueue
{
    use ChecksAzureFileExistence, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $refId = "{$this->uuid} - {$this->quoteDocumentId}";
        LoggerService::startQuoteLogging($refId, feature: LoggerFeatureEnum::WATERMARK_DOCUMENT);

        LoggerService::info('WatermarkDocumentsJob started');

        if ($this->isFileBeingProcessed()) {
            // Release the job to be retried in 30 seconds so a legitimate second attempt (e.g. after a stale cache lock) is not permanently lost.
            $this->release(30);

            return;
        }

        $quoteDocumentAndType = $this->resolveQuoteDocumentAndDocumentType();
        if ($quoteDocumentAndType === null) {
            return;
        }

        [$quoteDocument, $documentType] = $quoteDocumentAndType;
        $sourcePath = $quoteDocument->doc_url;

        try {
            // Check if the source file exists
            $sourcePath = (string) ($quoteDocument->doc_url ?? '');
            if ($sourcePath === '') {
                LoggerService::warning('Source file path is empty');

                return;
            }

            if (! $this->fileExists($sourcePath)) {
                LoggerService::warning("Source file does not exist: {$sourcePath}");

                return;
            }

            // Perform watermarking based on file type
            $watermarkService = app(QuoteDocumentService::class);
            $fileMimeType = $quoteDocument->doc_mime_type;
            $docName = str_replace('original_', '', $quoteDocument->doc_name);

            $extension = strtolower(pathinfo($quoteDocument->doc_name, PATHINFO_EXTENSION));

            LoggerService::info('Watermark starting for document ID', [
                'doc_name' => $docName,
                'fileMimeType' => $fileMimeType,
                'documentType' => $documentType->code,
            ]);

            if ($fileMimeType == 'application/pdf' || $fileMimeType == '.pdf' || $extension == 'pdf') {
                $watermarkData = $watermarkService->watermarkPdf($quoteDocument->doc_url, $docName, $this->uuid, $documentType);
            } elseif (in_array($fileMimeType, ['image/jpeg', 'image/png', 'image/jpg'])) {
                $watermarkData = $watermarkService->watermarkImage($quoteDocument->doc_url, $docName, $this->uuid, $documentType);
            } elseif (in_array($fileMimeType, ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/msword'])) {
                $watermarkData = $watermarkService->watermarkWordDocs($quoteDocument->doc_url, $docName, $this->uuid, $documentType);
            }

            // Update the document with watermark data
            if (isset($watermarkData['watermarked_doc_name'], $watermarkData['watermarked_doc_url'])) {
                $quoteDocument->update([
                    'watermarked_doc_name' => $watermarkData['watermarked_doc_name'],
                    'watermarked_doc_url' => $watermarkData['watermarked_doc_url'],
                ]);
                LoggerService::info('Watermark job completed');
            }
        } catch (\Exception $e) {
            cache()->forget("processing_{$this->lockKey}");
            LoggerService::error('Error processing watermark. Error: '.$e->getMessage(), [], $e);
            throw $e; // Re-throw to trigger job retry
        } catch (Throwable $t) {
            // Ensure the processing lock is always cleared for non-Exception Throwables (e.g. TypeError, Error)
            LoggerService::error('Error processing watermark (Throwable): '.$t->getMessage(), [
                'throwable_class' => $t::class,
            ], $t);
            throw $t;
        } finally {
            cache()->forget("processing_{$this->lockKey}");
        }
    }

    /**
     * Resolve QuoteDocument and DocumentType by ID. Returns null if not found, doc_url is empty, or job is failed.
     *
     * @return array{0: QuoteDocument, 1: DocumentType}|null
     */
    private function resolveQuoteDocumentAndDocumentType(): ?array
    {
        try {
            $quoteDocument = QuoteDocument::findOrFail($this->quoteDocumentId);
            $documentType = DocumentType::findOrFail($this->documentTypeId);

            $sourcePath = (string) ($quoteDocument->doc_url ?? '');
            if ($sourcePath === '') {
                cache()->forget("processing_{$this->lockKey}");
                LoggerService::warning('Source file path is empty');

                return null;
            }

            return [$quoteDocument, $documentType];
        } catch (ModelNotFoundException $e) {
            cache()->forget("processing_{$this->lockKey}");
            LoggerService::warning('QuoteDocument or DocumentType not found; failing job.', [], $e);
            $this->fail($e);

            return null;
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
            LoggerService::info('File is already being processed; skipping this attempt.');

            return true;
        }

        // Set a processing flag with a 5-minute expiration
        cache()->put($cacheKey, true, now()->addMinutes(5));

        return false;
    }

    /**
     * Check if a file exists (remote URL or Azure storage).
     * Returns false only when existence was successfully checked and the file is missing.
     * Re-throws UnableToCheckExistence and other exceptions so the job retries on transient failures.
     */
    private function fileExists(string $path): bool
    {
        try {
            return $this->checkAzureFileExistsWithRetry($path);
        } catch (UnableToCheckExistence $e) {
            $previous = $e->getPrevious();
            LoggerService::warning(
                'Unable to check file existence (Azure transient failure): '.$path,
                [
                    'previous_exception_class' => $previous ? $previous::class : null,
                    'previous_exception_message' => $previous?->getMessage(),
                ],
                $e
            );
            throw $e;
        } catch (Throwable $e) {
            $previous = $e->getPrevious();
            LoggerService::error(
                'Error checking file existence: '.$path,
                [
                    'exception_class' => $e::class,
                    'previous_exception_class' => $previous ? $previous::class : null,
                    'previous_exception_message' => $previous?->getMessage(),
                ],
                $e
            );
            throw $e;
        }
    }

    public function middleware()
    {
        // Use a more specific lock key and increase the lock duration
        return [(new WithoutOverlapping($this->lockKey))->dontRelease()->expireAfter(300)];
    }
}
