<?php

namespace App\Services;

use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\ClaimRequest;
use App\Models\DocumentType;
use App\Services\Logger\LoggerService;
use App\Traits\CentralTrait;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ClaimDocumentService extends BaseService
{
    use CentralTrait;

    protected ClaimStatusesService $claimsStatusesService;

    public function __construct(ClaimStatusesService $claimsStatusesService)
    {
        parent::__construct();
        $this->claimsStatusesService = $claimsStatusesService;
    }

    /**
     * Retrieve an active document type by its code.
     */
    public function getDocumentTypeByCode(string $code): ?DocumentType
    {
        return DocumentType::where('code', $code)
            ->where('is_active', 1)
            ->first();
    }

    public function getClaimDocumentTypes($quoteTypeId, $businessTypeOfInsuranceId = null)
    {
        $resolvedQuoteTypeId = $this->resolveQuoteTypeIdForDocuments($quoteTypeId, $businessTypeOfInsuranceId);

        $claimDocumentTypes = DocumentType::active()
            ->whereIn('category', [DocumentTypeCode::CLAIM])
            ->where('quote_type_id', $resolvedQuoteTypeId)
            ->sortDocumentType()
            ->get();

        $documentTypesByCategory = $claimDocumentTypes->groupBy('category');
        $orderedDocumentTypesByCategory = collect();

        if ($documentTypesByCategory->has(DocumentTypeCode::CLAIM)) {
            $orderedDocumentTypesByCategory->put(DocumentTypeCode::CLAIM, $documentTypesByCategory->get(DocumentTypeCode::CLAIM));
        }

        return $orderedDocumentTypesByCategory;
    }

    protected function resolveQuoteTypeIdForDocuments($quoteTypeId, $businessTypeOfInsuranceId = null)
    {
        if ($quoteTypeId == QuoteTypeId::Business && $businessTypeOfInsuranceId && $businessTypeOfInsuranceId == BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL) {
            return QuoteTypeId::Health;
        }

        return $quoteTypeId;
    }

    /**
     * Upload multiple documents for a claim
     */
    public function uploadClaimDocuments(ClaimRequest $claim, array $files, array $documentData): array
    {
        $uploadedDocuments = [];
        $errors = [];

        foreach ($files as $file) {
            try {
                $document = app(QuoteDocumentService::class)->uploadQuoteDocument(
                    $file,
                    array_merge($documentData, [
                        'claim_id' => $claim->id,
                        'quote_id' => $claim->id,
                        'claim_uuid' => $claim->uuid,
                        'quote_uuid' => $claim->uuid,
                    ]),
                    $claim
                );

                if ($document) {
                    $uploadedDocuments[] = $document;

                    LoggerService::info(' Document uploaded successfully', extra: [
                        'claim_uuid' => $claim->uuid,
                        'document_id' => $document->id ?? null,
                        'document_name' => $document->original_name ?? 'Unknown',
                        'document_type' => $documentData['document_type_code'],
                        'user_id' => Auth::id(),
                    ]);
                } else {
                    $errors[] = "Failed to upload document: {$file->getClientOriginalName()}";
                }
            } catch (Exception $e) {
                $errors[] = "Error uploading {$file->getClientOriginalName()}: {$e->getMessage()}";

                LoggerService::error(' Document upload failed', extra: [
                    'claim_uuid' => $claim->uuid,
                    'file_name' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                    'user_id' => Auth::id(),
                ], exception: $e);
            }
        }

        return [
            'uploaded_documents' => $uploadedDocuments,
            'errors' => $errors,
            'success_count' => count($uploadedDocuments),
            'error_count' => count($errors),
        ];
    }

    /**
     * Delete a claim document with validation
     */
    public function deleteClaimDocument(ClaimRequest $claim, $documentId): bool
    {
        $document = $claim->documents()->where('id', $documentId)->first();

        if (! $document) {
            LoggerService::warning(' Document not found', extra: [
                'claim_uuid' => $claim->uuid,
                'document_id' => $documentId,
                'user_id' => Auth::id(),
            ]);

            return false;
        }

        return $document->delete();
    }

    /**
     * Create and download ZIP file containing all claim documents
     *
     * @throws Exception
     */
    public function createDocumentsZip(ClaimRequest $claim): array
    {
        $documents = $claim->documents;

        $this->validateDocumentsForZip($documents);

        $zipFileName = $this->generateZipFileName($claim);
        $zipFilePath = storage_path('temp/'.$zipFileName);

        $result = [
            'success' => false,
            'file_path' => null,
            'file_name' => $zipFileName,
            'processed_count' => 0,
            'total_count' => count($documents),
            'errors' => [],
        ];

        try {
            $zip = new ZipArchive;

            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception('Could not create ZIP file at: '.$zipFilePath);
            }

            $processedDocuments = $this->addDocumentsToZip($zip, $documents, $claim);
            $zip->close();

            if (empty($processedDocuments)) {
                $this->cleanupZipFile($zipFilePath);
                throw new Exception('No documents were successfully added to the ZIP file');
            }

            $result['success'] = true;
            $result['file_path'] = $zipFilePath;
            $result['processed_count'] = count($processedDocuments);

            LoggerService::info(' ZIP file created successfully', extra: [
                'claim_uuid' => $claim->uuid,
                'processed_documents_count' => count($processedDocuments),
                'zip_file_name' => $zipFileName,
                'user_id' => Auth::id(),
            ]);

            return $result;

        } catch (Exception $e) {
            $this->cleanupZipFile($zipFilePath);

            LoggerService::error(' Error creating ZIP file', extra: [
                'claim_uuid' => $claim->uuid,
                'error' => $e->getMessage(),
                'zip_file_path' => $zipFilePath,
                'user_id' => Auth::id(),
            ]);

            throw $e;
        }
    }

    /**
     * Validate documents for ZIP creation
     *
     * @throws Exception
     */
    private function validateDocumentsForZip($documents): void
    {
        if (! $documents || (is_countable($documents) && count($documents) === 0)) {
            throw new Exception('No documents available for this claim');
        }

        // If it's a collection, convert to array for processing
        if ($documents instanceof Collection) {
            $documents = $documents->toArray();
        }

        // Validate document structure
        foreach ($documents as $document) {
            $docUrl = is_array($document) ? ($document['doc_url'] ?? null) : $document->doc_url ?? null;
            $originalName = is_array($document) ? ($document['original_name'] ?? null) : $document->original_name ?? null;

            if (! $docUrl || ! $originalName) {
                throw new Exception('Invalid document structure: missing required fields (doc_url or original_name)');
            }
        }
    }

    /**
     * Generate ZIP file name for claim documents
     */
    private function generateZipFileName(ClaimRequest $claim): string
    {
        $firstName = $this->sanitizeFileName($claim->first_name ?? 'Customer');
        $lastName = $this->sanitizeFileName($claim->last_name ?? 'Docs');
        $claimCode = $this->sanitizeFileName($claim->uuid);

        return "Claim_{$claimCode}_{$firstName}_{$lastName}_".date('Y-m-d_H-i-s').'.zip';
    }

    /**
     * Sanitize filename to remove invalid characters
     */
    private function sanitizeFileName(string $filename): string
    {
        // Remove or replace invalid filename characters
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);

        return substr($filename, 0, 50); // Limit length
    }

    /**
     * Add documents to ZIP archive
     */
    private function addDocumentsToZip(ZipArchive $zip, $documents, ClaimRequest $claim): array
    {
        $disk = Storage::disk('azureIM');
        $processedDocuments = [];
        $documentCounts = []; // Track duplicate names

        // Convert collection to array if needed
        if ($documents instanceof Collection) {
            $documents = $documents->toArray();
        }

        foreach ($documents as $document) {
            try {
                // Handle both array and object formats
                $docUrl = is_array($document) ? $document['doc_url'] : $document->doc_url;
                $originalName = is_array($document) ? $document['original_name'] : $document->original_name;
                $documentId = is_array($document) ? ($document['id'] ?? null) : $document->id ?? null;

                if (! $disk->exists($docUrl)) {
                    LoggerService::warning(' Document does not exist', extra: [
                        'doc_url' => $docUrl,
                        'document_name' => $originalName,
                        'document_id' => $documentId,
                        'claim_uuid' => $claim->uuid,
                    ]);

                    continue;
                }

                // Handle duplicate filenames
                $finalName = $this->getUniqueFileName($originalName, $documentCounts);

                $contents = $disk->get($docUrl);

                if ($zip->addFromString($finalName, $contents)) {
                    $processedDocuments[] = [
                        'name' => $finalName,
                        'original_name' => $originalName,
                        'id' => $documentId,
                    ];
                } else {
                    LoggerService::warning(' Failed to add document to ZIP', extra: [
                        'document_name' => $originalName,
                        'final_name' => $finalName,
                        'document_id' => $documentId,
                        'claim_uuid' => $claim->uuid,
                    ]);
                }

            } catch (Exception $e) {
                $documentName = 'unknown';
                try {
                    $documentName = is_array($document) ? ($document['original_name'] ?? 'unknown') : $document->original_name ?? 'unknown';
                } catch (Exception $nameEx) {
                    // Fallback if we can't get the name
                }

                LoggerService::warning(' Error processing document', extra: [
                    'document_name' => $documentName,
                    'error' => $e->getMessage(),
                    'claim_uuid' => $claim->uuid,
                ]);
            }
        }

        return $processedDocuments;
    }

    /**
     * Get unique filename to handle duplicates
     */
    private function getUniqueFileName(string $originalName, array &$documentCounts): string
    {
        if (! isset($documentCounts[$originalName])) {
            $documentCounts[$originalName] = 1;

            return $originalName;
        }

        $documentCounts[$originalName]++;
        $pathInfo = pathinfo($originalName);
        $name = $pathInfo['filename'] ?? $originalName;
        $extension = isset($pathInfo['extension']) ? '.'.$pathInfo['extension'] : '';

        return $name.'_('.$documentCounts[$originalName].')'.$extension;
    }

    /**
     * Clean up ZIP file if it exists
     */
    private function cleanupZipFile(string $zipFilePath): void
    {
        if (file_exists($zipFilePath)) {
            try {
                unlink($zipFilePath);
            } catch (Exception $e) {
                LoggerService::warning(' Failed to cleanup ZIP file', extra: [
                    'file_path' => $zipFilePath,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

}
