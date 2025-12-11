<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Health\Adnic;

use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypes;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
use App\Models\PersonalQuote;

class AdnicDocumentHandler {

    private const ALLOWED_DOCUMENT_MIME_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];

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
            LoggerService::error('Document fetch failed', extra: [
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
        return rtrim(config('constants.AZURE_IM_STORAGE_URL'), '/'). '/' . rtrim(config('constants.AZURE_IM_STORAGE_CONTAINER'), '/') . '/' . ltrim($relativePath, '/');
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
        $quoteType = QuoteTypes::HEALTH->value;
        // Ensure is_base_64 flag is set in data for proper handling
        $data['is_base_64'] = 1;
        $data['quote_uuid'] = $quote->uuid;
        $data['quote_type'] = $quoteType;
        $data['file_name'] = $originalName;
        $data['document_type_code'] = $documentCode;

        $quoteDocumentService = new QuoteDocumentService;
        return $quoteDocumentService->uploadQuoteDocument($documentContent, $data, $quote);
    }

    /**
     * Map document types to AWNI document type codes
     *
     * @param string $documentType
     * @return string|null
     */
    public function getDocTypeCodeForHealth(string $documentType): string | null
    {
        return match ($documentType) {
            DocumentTypeCode::HEA_EID => '4', // Emirates ID (Front side & Back side)
            default => null
        };
    }

    /**
     * Map document types to IMCRM document type codes
     *
     * @param PersonalQuote $quote
     * @return array
     */
    public function getDocTypeCodeForIMCRM(PersonalQuote $quote): array
    {
        return [
            DocumentTypeCode::HEA_EID => $quote->insurer_tax_invoice_doc_id,
            DocumentTypeCode::CTIRBB => $quote->insurer_debit_note_doc_id,
            DocumentTypeCode::POLICY_SCHEDULE => $quote->insurer_policy_doc_id,
        ];
    }
}
