<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\EpBookingContext;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\QuoteTypes;
use App\Models\DocumentType;
use App\Models\QuoteDocument;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Error;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;

class EpBookingService extends BaseService
{
    use GenericQueriesAllLobs;

    protected string $logPrefix = 'EpBooking - Service:';
    protected array $logExtra = [];

    public mixed $quote = null;

    /**
     * Create a new class instance.
     */
    protected function __construct(
        public string $epServiceName,
        public EpBookingContext $context
    ) {
        $this->logPrefix = "{$epServiceName}Service:";
        
        $quoteType = QuoteTypes::getName($this->context->quoteTypeId)->value;
        $this->quote = $this->getQuoteObject($quoteType, $this->context->quoteId);
    }
    
    public static function buildContext(int $etId, string $quoteId, int $quoteTypeId, string $quoteCode)
    {
        $epBookingContext = new EpBookingContext(
            etId: $etId,
            quoteId: $quoteId,
            quoteTypeId: $quoteTypeId,
            quoteCode: $quoteCode
        );

        return $epBookingContext;
    }

    public function processWatermarkDocuments(Collection $documents, array $watermarkableDocTypeCodes): array
    {
        $watermarkedDocuments = [];
        $documentTypes = DocumentType::whereIn('code', $watermarkableDocTypeCodes)
            ->where('quote_type_id', $this->context->quoteTypeId)->get();

        if ((count($documents) > 0)) {
            foreach ($documents as $documentItem) {

                // skip iteration when document_type_code is not from initial document types
                if (! in_array($documentItem->document_type_code, $watermarkableDocTypeCodes)) {
                    continue;
                }

                // skip iteration when document is already watermarked
                if ($documentItem->is_watermarked) {
                    continue;
                }

                $documentType = $documentTypes->firstWhere('code', $documentItem->document_type_code);

                if (!$documentType) {
                    LoggerService::warning("DocumentType not found for code: {$documentItem->document_type_code}");
                    continue;
                }

                $savedWatermarkedDocument = $this->watermarkDocument($documentItem, $documentType);
                if (! empty($savedWatermarkedDocument)) {
                    $watermarkedDocuments[] = $savedWatermarkedDocument;
                }
            }
        }

        return $watermarkedDocuments;
    }

    public function watermarkDocument(QuoteDocument $quoteDocument, DocumentType $documentType): QuoteDocument|false
    {
        $extraLog = [
            ...$this->context->logExtra,
            'document_id' => $quoteDocument?->id,
            'document_type_code' => $quoteDocument?->document_type_code
        ];

        if (! $quoteDocument) {
            LoggerService::warning('Document not found', extra: $extraLog);

            return false;
        }

        // Ensure the quoteDocument and documentType exist
        if (! $documentType) {
            LoggerService::warning('DocumentType not found', extra: $extraLog);

            return false;
        }

        $lockKey = "watermark_{$quoteDocument->id}_{$this->quote->uuid}_{$documentType->id}";

        // Check if the file is already being processed
        if ($this->isFileBeingProcessed($lockKey)) {
            LoggerService::info("File is already being processed. Retrying later. Document ID: {$quoteDocument->id}, UUID: {$this->quote->uuid}");

            return false;
        }

        // Check if the source file exists
        if (empty($quoteDocument->doc_url) || ! $this->fileExists($quoteDocument->doc_url)) {
            LoggerService::error("Source file does not exist: {$quoteDocument->doc_url}");

            return false;
        }

        try {
            // Perform watermarking based on file type
            $watermarkService = app()->make(QuoteDocumentService::class);
            $fileMimeType = $quoteDocument->doc_mime_type;
            $docName = str_replace('original_', '', $quoteDocument->doc_name);

            $extension = strtolower(pathinfo($quoteDocument->doc_name, PATHINFO_EXTENSION));

            if ($fileMimeType == 'application/pdf' || $fileMimeType == '.pdf' || $extension == 'pdf') {
                $watermarkData = $watermarkService->watermarkPdf($quoteDocument->doc_url, $docName, $this->quote->uuid, $documentType);
            } else {
                LoggerService::error("Unsupported file type: fileMimeType: {$fileMimeType}, extension: {$extension}");

                return false;
            }

            // Update the document with watermark data
            if (isset($watermarkData['watermarked_doc_name']) && isset($watermarkData['watermarked_doc_url'])) {
                $quoteDocument->update([
                    'watermarked_doc_name' => $watermarkData['watermarked_doc_name'],
                    'watermarked_doc_url' => $watermarkData['watermarked_doc_url'],
                ]);
                LoggerService::info('watermark job completed for '.$this->quote->uuid);
            }

            return $quoteDocument;
        } catch (\Exception $e) {
            LoggerService::error("Error processing watermark for document ID: {$quoteDocument->id}, UUID: {$this->quote->uuid}. Error: ".$e->getMessage());

            return false;
        }
    }

    protected function getRequiredDocTypeCodes(): array
    {
        return [
            QuoteDocumentsEnum::POLICY_SCHEDULE,
            QuoteDocumentsEnum::CAR_TAX_INVOICE,
            QuoteDocumentsEnum::CAR_TAX_INVOICE_RAISE_BY_BUYER
        ];
    }

    /**
     * Check if the file is already being processed
     */
    private function isFileBeingProcessed($lockKey)
    {
        // Use cache to track processing status
        $cacheKey = "processing_{$lockKey}";
        $lockAcquired = cache()->add($cacheKey, true, now()->addMinutes(5));

        return ! $lockAcquired;
    }

    /**
     * Extract filename from HTTP response headers or URL
     */
    protected function extractFilename(string $url): string
    {
        return basename(parse_url($url, PHP_URL_PATH)) ?? 'document';
    }
    
    /**
     * Check if a file exists
     */
    private function fileExists(string $path): bool
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

    /**
     * Upload policy document.
     *
     * $response = $this->uploadDocument($fileName, $fileContent, $dir);
     * @return string The generated UUID.
     */
    protected function uploadDocument($docName, $fileContent, $dir): array
    {
        try {
            $fileNameAzure = uniqid() . "_{$docName}";
            $docUrl = "{$dir}/{$fileNameAzure}";
            $isSuccess = Storage::disk('azureIM')->put($docUrl, $fileContent);

            if (! $isSuccess) {
                throw new Error("Process Failed, doc_name: {$docName}, doc_url: {$docUrl}");
            }

            return [
                'success' => $isSuccess,
                'statusCode' => 200,
                'statusMessage' => 'Success',
                'data' => ['doc_name' => $docName, 'doc_url' => $docUrl]
            ];
        } catch (Throwable $e) {

            return [
                'success' => false,
                'statusCode' => 402,
                'error' => "UploadDocument: ".$e->getMessage(),
            ];
        }
    }
}
