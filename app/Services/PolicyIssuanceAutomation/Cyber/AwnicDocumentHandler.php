<?php

namespace App\Services\PolicyIssuanceAutomation\Cyber;

use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;

class AwnicDocumentHandler
{
    private const ALLOWED_DOCUMENT_MIME_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];

    /**
     * Fetch document content from Azure storage
     */
    public function fetchDocumentContent(string $relativePath): array
    {
        $result = ['status' => false];
        $filePath = $this->buildAzureDocumentPath($relativePath);
        if (! $filePath) {
            $result['message'] = 'Failed to generate temporary URL for document';
            LoggerService::error('Document URL generation failed', extra: [
                'relative_path' => $relativePath,
                'error' => $result['message'],
            ]);
        } else {
            $fileContent = @file_get_contents($filePath);

            if ($fileContent === false || $fileContent === '') {
                $result['message'] = 'Invalid or empty document content';
                LoggerService::error('Document fetch failed', extra: [
                    'file_path' => $filePath,
                    'relative_path' => $relativePath,
                    'error' => $result['message'],
                ]);
            } else {
                $mimeType = $this->detectMimeType($fileContent);
                if (! $mimeType || ! in_array($mimeType, self::ALLOWED_DOCUMENT_MIME_TYPES, true)) {
                    $result['message'] = 'Unsupported document type: '.($mimeType ?? 'unknown');
                    LoggerService::error('Invalid document mime type', extra: [
                        'file_path' => $filePath,
                        'mime_type' => $mimeType,
                        'allowed_types' => self::ALLOWED_DOCUMENT_MIME_TYPES,
                        'error' => $result['message'],
                    ]);
                } else {
                    $result['status'] = true;
                    $result['content'] = $fileContent;
                }
            }
        }

        return $result;
    }

    /**
     * Build Azure document path from relative path
     */
    private function buildAzureDocumentPath(string $relativePath): ?string
    {
        $quoteDocumentService = app(QuoteDocumentService::class);

        // Use getDocumentUrl to get the actual URL string, not the JSON response
        $url = $quoteDocumentService->getDocumentUrl($relativePath);

        if (! $url) {
            LoggerService::error('Failed to generate temporary URL for document', extra: [
                'relative_path' => $relativePath,
                'url' => $url,
            ]);

            return null;
        }

        return $url;
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
        $quoteType = QuoteTypes::CYBER->value;
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
     */
    public function getDocTypeCodeForCyber(string $documentType): ?string
    {
        return match ($documentType) {
            DocumentTypeCode::CYB_EID => '4', // Emirates ID (Front side & Back side)
            default => null
        };
    }

    /**
     * Map document types to IMCRM document type codes
     */
    public function getDocTypeCodeForIMCRM(PersonalQuote $quote): array
    {
        return [
            DocumentTypeCode::CYB_TI => $quote->cyberQuote?->insurer_tax_invoice_doc_id,
            DocumentTypeCode::CYB_TIRBB => $quote->cyberQuote?->insurer_debit_note_doc_id,
            DocumentTypeCode::CYB_PS => $quote->cyberQuote?->insurer_policy_doc_id,
        ];
    }
}
