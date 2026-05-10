<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance;

use App\Enums\DocumentTypeCode;
use App\Enums\NgiEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Models\PolicyIssuance;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\QuoteDocumentService;
use Illuminate\Http\Client\Response;

class NgiDocumentHandler
{
    private const ERROR_MESSAGE_DOCUMENT_FETCH_FAILED = 'Document fetch failed';

    public function __construct(
        private NgiHttpClient $httpClient,
    ) {}

    /**
     * Fetch document content from URL
     */
    public function fetchDocumentFromUrl(string $documentUrl): array
    {
        try {
            LoggerService::info('Downloading document from NGI', extra: [
                'url' => $documentUrl,
            ]);

            $response = $this->httpClient->getAuthenticatedClient()->get($documentUrl);

            if ($response->failed()) {
                LoggerService::error('NGI document download failed', extra: [
                    'url' => $documentUrl,
                    'status_code' => $response->status(),
                ]);
            } else {
                LoggerService::info('NGI document download completed', extra: [
                    'url' => $documentUrl,
                    'status_code' => $response->status(),
                ]);
            }

            // Handle download failure or empty content in one check
            $errorMessage = $this->getDownloadErrorMessage($response);
            if ($errorMessage !== null) {
                $this->logDocumentFetchError($errorMessage, $documentUrl, $response->failed() ? $response->status() : null);

                return ['status' => false, 'message' => $errorMessage];
            }

            return ['status' => true, 'content' => $response->body()];
        } catch (\Exception $e) {
            LoggerService::error('Document fetch exception', extra: [
                'url' => $documentUrl,
            ], exception: $e);

            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get error message for download response, or null if successful
     *
     * @param  Response  $response
     */
    private function getDownloadErrorMessage($response): ?string
    {
        if ($response->failed()) {
            return 'Failed to download document from URL';
        }

        if (empty($response->body())) {
            return 'Invalid or empty document content';
        }

        return null;
    }

    /**
     * Log document fetch error with context
     */
    private function logDocumentFetchError(string $message, string $documentUrl, ?int $statusCode = null): void
    {
        $extra = [
            'url' => $documentUrl,
            'error' => $message,
        ];

        if ($statusCode !== null) {
            $extra['status_code'] = $statusCode;
        }

        LoggerService::error(self::ERROR_MESSAGE_DOCUMENT_FETCH_FAILED, extra: $extra);
    }

    /**
     * Build Azure document path from relative path
     */
    private function buildAzureDocumentPath(string $relativePath): string
    {
        return rtrim(config('constants.AZURE_IM_STORAGE_URL', ''), '/').'/'.rtrim(config('constants.AZURE_IM_STORAGE_CONTAINER', ''), '/').'/'.ltrim($relativePath, '/');
    }

    /**
     * Detect MIME type of file content
     */
    private function detectMimeType(string $fileContent): ?string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (! $finfo) {
            return null;
        }

        $mimeType = finfo_buffer($finfo, $fileContent) ?: null;
        finfo_close($finfo);

        return $mimeType;
    }

    /**
     * Get document by type from quote documents
     *
     * @param  mixed  $quote
     */
    public function getDocumentByType($quote, string $documentTypeCode): ?array
    {
        $documents = collect($quote->documents ?? []);
        $documents = $documents->where('document_type_code', $documentTypeCode);

        if ($documents->isEmpty() || $documents->count() === 0) {
            return null;
        }

        return is_array($documents) ? $documents : $documents->toArray();
    }

    /**
     * Upload document to IMCRM and attach to quote
     *
     * @param  mixed  $quote
     * @param  string  $documentContent  Base64 encoded document content
     * @param  string  $documentCode
     * @param  string|null  $originalName
     * @return mixed
     */
    public function uploadAndAttachToQuoteDocuments($quote, $documentContent, $documentCode, $originalName = null)
    {
        $quoteType = QuoteTypes::DEVICE->value;
        $data['is_base_64'] = 1;
        $data['quote_uuid'] = $quote->uuid;
        $data['quote_type'] = $quoteType;
        $data['file_name'] = $originalName;
        $data['document_type_code'] = $documentCode;

        return app(QuoteDocumentService::class)->uploadQuoteDocument($documentContent, $data, $quote);
    }

    /**
     * Download all policy documents from provider URLs and store in database
     */
    public function downloadAndStorePolicyDocuments(PersonalQuote $quote, PolicyIssuance $process, array $documentsApiResponse): array
    {
        $quote->refresh(); // Refresh quote to get latest quote object

        $documentsApiResponseData = $documentsApiResponse['data'] ?? null;

        // NGI exposes policy_certificate_url only (no separate policy schedule document).
        $documentUrls = $this->buildNgiPolicyDocumentUrlMap($documentsApiResponseData);

        // Validate document URLs exist
        $validationService = app(NgiValidationService::class);
        $validationResult = $validationService->validateDownloadDocuments($documentUrls);
        if (! $validationResult['status']) {
            return $validationResult;
        }

        $downloadedDocuments = collect();
        $failedDocuments = [];

        foreach ($documentUrls as $docCode => $documentUrl) {
            if (empty($documentUrl)) {
                $failedDocuments[] = $docCode;
                LoggerService::error('NgiDocumentHandler: Empty document URL', [
                    'document_code' => $docCode,
                ]);

                continue;
            }

            LoggerService::info('NgiDocumentHandler: Downloading document', [
                'document_code' => $docCode,
                'document_url' => $documentUrl,
            ]);

            // Download document content from provider URL
            $documentContentResponse = $this->fetchDocumentFromUrl($documentUrl);

            if (! $documentContentResponse['status']) {
                $failedDocuments[] = $docCode;
                LoggerService::warning('NgiDocumentHandler: Document download failed', [
                    'document_code' => $docCode,
                    'error' => $documentContentResponse['message'] ?? 'Download failed',
                ]);

                continue;
            }

            // Generate file name
            $fileName = $this->getDocumentFileName($docCode, $quote->policy_number);

            // Detect MIME type from raw content
            $mimeType = $this->detectMimeType($documentContentResponse['content']);
            if ($mimeType === null) {
                $failedDocuments[] = $docCode;
                LoggerService::error('NgiDocumentHandler: Failed to detect MIME type', [
                    'document_code' => $docCode,
                ]);

                continue;
            }

            // Encode content to base64 and format as Data URL for storage
            $base64Content = 'data:'.$mimeType.';base64,'.base64_encode($documentContentResponse['content']);

            LoggerService::info('NgiDocumentHandler: Storing document in DB', [
                'document_code' => $docCode,
                'file_name' => $fileName,
            ]);

            // Store document in database
            $quoteDocument = $this->uploadAndAttachToQuoteDocuments(
                $quote,
                $base64Content,
                $docCode,
                $fileName
            );

            // Log the download action
            app(PolicyIssuanceService::class)->storePolicyIssuanceLog(
                $quote,
                ['document_url' => $documentUrl, 'document_code' => $docCode],
                ['stored' => (bool) $quoteDocument?->id, 'document_id' => $quoteDocument?->id],
                $documentUrl,
                NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
                $quoteDocument?->id ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS,
                $process
            );

            if (! $quoteDocument?->id) {
                $failedDocuments[] = $docCode;

                continue;
            }

            $downloadedDocuments->push([
                'code' => $docCode,
                'document_id' => $quoteDocument->id,
                'file_name' => $fileName,
            ]);
        }

        // Check if all 3 required documents were downloaded
        $allDocsDownloaded = $downloadedDocuments->count() === 3;

        if (! $allDocsDownloaded) {
            return [
                'status' => false,
                'error' => 'Failed to download documents: '.implode(', ', $failedDocuments),
                'documents_count' => $downloadedDocuments->count(),
                'failed_documents' => $failedDocuments,
            ];
        }

        return [
            'status' => true,
            'documents_count' => $downloadedDocuments->count(),
            'documents' => $downloadedDocuments->toArray(),
        ];
    }

    /**
     * Get document file name based on document type
     */
    private function getDocumentFileName(string $docCode, ?string $policyNumber): string
    {
        $prefix = $policyNumber ?? 'policy';

        return match ($docCode) {
            DocumentTypeCode::DEVICE_SMARTPHONE_POLICY_CERTIFICATE => $prefix.'_policy_certificate.pdf',
            DocumentTypeCode::DEVICE_SMARTPHONE_POLICY_SCHEDULE => $prefix.'_policy_schedule.pdf',
            DocumentTypeCode::DEVICE_SMARTPHONE_TAX_INVOICE => $prefix.'_tax_invoice.pdf',
            DocumentTypeCode::DEVICE_SMARTPHONE_TAX_INVOICE_RAISED_BY_BUYER => $prefix.'_commission_invoice.pdf',
            default => $prefix.'_document.pdf',
        };
    }

    /**
     * Map NGI GetPolicyDocuments fields to our issuing document type codes (one provider URL per logical doc).
     *
     * @return array<string, string|null>
     */
    private function buildNgiPolicyDocumentUrlMap(?object $policyDocumentsResult): array
    {
        return [
            DocumentTypeCode::DEVICE_SMARTPHONE_POLICY_CERTIFICATE => $policyDocumentsResult?->policy_certificate_url,
            DocumentTypeCode::DEVICE_SMARTPHONE_TAX_INVOICE => $policyDocumentsResult?->premium_inv_doc_url,
            DocumentTypeCode::DEVICE_SMARTPHONE_TAX_INVOICE_RAISED_BY_BUYER => $policyDocumentsResult?->commision_inv_doc_url,
        ];
    }
}
