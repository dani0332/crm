<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance;

use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;

class NgiDocumentHandler
{
    private const ALLOWED_DOCUMENT_MIME_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];
    private const ERROR_MESSAGE_DOCUMENT_FETCH_FAILED = 'Document fetch failed';

    public function __construct(
        private NgiHttpClient $httpClient,
    ) {}

    /**
     * Fetch document content from URL
     *
     * @param string $documentUrl
     * @return array
     */
    public function fetchDocumentFromUrl(string $documentUrl): array
    {
        try {
            $response = $this->httpClient->downloadDocument($documentUrl);

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
     * @param \Illuminate\Http\Client\Response $response
     * @return string|null
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
     *
     * @param string $message
     * @param string $documentUrl
     * @param int|null $statusCode
     * @return void
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
     * Fetch document content from Azure storage
     *
     * @param string $relativePath
     * @return array
     */
    public function fetchDocumentContent(string $relativePath): array
    {
        $filePath = $this->buildAzureDocumentPath($relativePath);
        $fileContent = @file_get_contents($filePath);

        if ($fileContent === false || $fileContent === '') {
            $message = 'Invalid or empty document content';
            LoggerService::error(self::ERROR_MESSAGE_DOCUMENT_FETCH_FAILED, extra: [
                'file_path' => $filePath,
                'relative_path' => $relativePath,
                'error' => $message,
            ]);

            return ['status' => false, 'message' => $message];
        }

        $mimeType = $this->detectMimeType($fileContent);
        if (! $mimeType || ! in_array($mimeType, self::ALLOWED_DOCUMENT_MIME_TYPES, true)) {
            $message = 'Unsupported document type: ' . ($mimeType ?? 'unknown');
            LoggerService::error('Invalid document mime type', extra: [
                'file_path' => $filePath,
                'mime_type' => $mimeType,
                'allowed_types' => self::ALLOWED_DOCUMENT_MIME_TYPES,
                'error' => $message,
            ]);

            return ['status' => false, 'message' => $message];
        }

        return ['status' => true, 'content' => $fileContent];
    }

    /**
     * Build Azure document path from relative path
     *
     * @param string $relativePath
     * @return string
     */
    private function buildAzureDocumentPath(string $relativePath): string
    {
        return rtrim(config('constants.AZURE_IM_STORAGE_URL'), '/') . '/' . rtrim(config('constants.AZURE_IM_STORAGE_CONTAINER'), '/') . '/' . ltrim($relativePath, '/');
    }

    /**
     * Detect MIME type of file content
     *
     * @param string $fileContent
     * @return string|null
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
     * @param mixed $quote
     * @param string $documentTypeCode
     * @return array|null
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
     * @param mixed $quote
     * @param string $documentContent Base64 encoded document content
     * @param string $documentCode
     * @param string|null $originalName
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

        $quoteDocumentService = new QuoteDocumentService;
        return $quoteDocumentService->uploadQuoteDocument($documentContent, $data, $quote);
    }

    /**
     * Get document URLs mapping from GetPolicyDocuments response
     *
     * @param object $policyDocumentsResponse
     * @return array
     */
    public function getDocumentUrlsFromResponse(object $policyDocumentsResponse): array
    {
        return [
            DocumentTypeCode::DEVICE_SMARTPHONE_POLICY_SCHEDULE => $policyDocumentsResponse->policy_certificate_url ?? null,
            DocumentTypeCode::DEVICE_SMARTPHONE_TAX_INVOICE => $policyDocumentsResponse->premium_inv_doc_url ?? null,
            DocumentTypeCode::DEVICE_SMARTPHONE_TAX_INVOICE_RAISED_BY_BUYER => $policyDocumentsResponse->commision_inv_doc_url ?? null,
        ];
    }

    /**
     * Map document types to IMCRM document type codes for Device/Smartphone
     *
     * @param PersonalQuote $quote
     * @return array
     */
    public function getDocTypeCodeForIMCRM(PersonalQuote $quote): array
    {
        return [
            DocumentTypeCode::DEVICE_SMARTPHONE_POLICY_SCHEDULE => $quote->insurer_policy_doc_id,
            DocumentTypeCode::DEVICE_SMARTPHONE_TAX_INVOICE => $quote->insurer_tax_invoice_doc_id,
            DocumentTypeCode::DEVICE_SMARTPHONE_TAX_INVOICE_RAISED_BY_BUYER => $quote->insurer_debit_note_doc_id,
        ];
    }
}
