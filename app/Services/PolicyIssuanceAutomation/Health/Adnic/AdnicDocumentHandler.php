<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Health\Adnic;

use App\Enums\AdnicEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypes;
use App\Models\HealthUMAFResponse;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
use Illuminate\Support\Collection;

class AdnicDocumentHandler
{
    private const ALLOWED_DOCUMENT_MIME_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];

    public function __construct(
        private QuoteDocumentService $quoteDocumentService,
    ) {}

    /**
     * Fetch document content from Azure storage
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
            $message = 'Unsupported document type: '.($mimeType ?? 'unknown');
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
     */
    private function buildAzureDocumentPath(string $relativePath): string
    {
        return $this->quoteDocumentService->getDocumentUrl($relativePath);
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
    public function getDocumentByType($quote, array $documentTypeCodes): ?Collection
    {
        $documents = collect($quote->documents ?? []);
        $documents = $documents->whereIn('document_type_code', $documentTypeCodes);

        if ($documents->isEmpty()) {
            return null;
        }

        return $this->finalizeDocumentsAfterTypeFilter($quote, $documents, $documentTypeCodes);
    }

    /**
     * @param  mixed  $quote
     */
    private function finalizeDocumentsAfterTypeFilter($quote, Collection $documents, array $documentTypeCodes): ?Collection
    {
        $emirateIdType = null;
        if (in_array(DocumentTypeCode::HEA_EMIRATE_ID_COPY, $documentTypeCodes, true)) {
            $emirateIdType = $this->modifyEmirateDocument($quote->uuid);
        }

        $documents = $this->keepLatestDocumentsPerType($documents, $emirateIdType);

        if ($documents->isEmpty()) {
            return null;
        }

        if (! in_array(DocumentTypeCode::HEA_EMIRATE_ID_COPY, $documentTypeCodes, true)) {
            return $documents;
        }

        return $this->applyEmiratesIdCopyDocumentRules($documents, $emirateIdType);
    }

    /**
     * Adjust HEA_EMIRATE_ID_COPY rows for physical EID (front/back) or insured application flow.
     */
    private function applyEmiratesIdCopyDocumentRules(Collection $documents, ?int $emirateIdType): Collection
    {
        if ($emirateIdType === AdnicEnum::EMIRATES_ID_CODE) {
            return $this->splitPhysicalEmiratesIdIntoFrontAndBack($documents);
        }

        if ($emirateIdType === AdnicEnum::INSURED_EMIRATES_ID_APPLICATION_CODE) {
            return $this->rewriteFirstEmirateIdCopyAsInsuredApplication($documents);
        }

        return $documents;
    }

    /**
     * Map first/second HEA_EMIRATE_ID_COPY to front/back when two files exist; otherwise keep copy code.
     *
     * @return array<int, int>
     */
    private function indicesOfEmirateIdCopyDocuments(array $documentsArray): array
    {
        $indices = [];
        foreach ($documentsArray as $index => $doc) {
            if ($this->getDocumentTypeCode($doc) === DocumentTypeCode::HEA_EMIRATE_ID_COPY) {
                $indices[] = $index;
            }
        }

        return $indices;
    }

    private function splitPhysicalEmiratesIdIntoFrontAndBack(Collection $documents): Collection
    {
        $emirateIdDocuments = $documents->where('document_type_code', DocumentTypeCode::HEA_EMIRATE_ID_COPY);
        $documentsArray = $documents->values()->all();
        $emirateIdIndices = $this->indicesOfEmirateIdCopyDocuments($documentsArray);

        if ($emirateIdDocuments->count() > 1) {
            if (isset($emirateIdIndices[0])) {
                $documentsArray[$emirateIdIndices[0]]['document_type_code'] = DocumentTypeCode::HEA_EID_FRONT;
            }
            if (isset($emirateIdIndices[1])) {
                $documentsArray[$emirateIdIndices[1]]['document_type_code'] = DocumentTypeCode::HEA_EID_BACK;
            }
        } elseif (isset($emirateIdIndices[0])) {
            $documentsArray[$emirateIdIndices[0]]['document_type_code'] = DocumentTypeCode::HEA_EMIRATE_ID_COPY;
        }

        return collect($documentsArray);
    }

    private function rewriteFirstEmirateIdCopyAsInsuredApplication(Collection $documents): Collection
    {
        $documentsArray = $documents->values()->all();

        foreach ($documentsArray as $index => $doc) {
            if ($this->getDocumentTypeCode($doc) === DocumentTypeCode::HEA_EMIRATE_ID_COPY) {
                $documentsArray[$index]['document_type_code'] = DocumentTypeCode::HEA_INSURED_EMIRATES_ID_APPLICATION;
                break;
            }
        }

        return collect($documentsArray);
    }

    /**
     * Resolve document_type_code from an array, object, or Eloquent model row.
     */
    private function getDocumentTypeCode(mixed $doc): string
    {
        if (is_array($doc)) {
            return (string) ($doc['document_type_code'] ?? '');
        }

        if (is_object($doc)) {
            return (string) ($doc->document_type_code ?? '');
        }

        return '';
    }

    private function getDocumentId(mixed $doc): int
    {
        if (is_array($doc)) {
            return (int) ($doc['id'] ?? 0);
        }

        if (is_object($doc)) {
            return (int) ($doc->id ?? 0);
        }

        return 0;
    }

    /**
     * Keep one newest row per document_type_code (by primary key). For physical Emirates ID, keep the two
     * newest HEA_EMIRATE_ID_COPY rows so front/back split can still run.
     */
    private function keepLatestDocumentsPerType(Collection $documents, ?int $emirateIdType): Collection
    {
        $grouped = $documents->groupBy(fn (mixed $doc) => $this->getDocumentTypeCode($doc));

        return $grouped->flatMap(function (Collection $group, string $typeCode) use ($emirateIdType) {
            $sorted = $group->sortByDesc(fn (mixed $doc) => $this->getDocumentId($doc))->values();

            if (
                $typeCode === DocumentTypeCode::HEA_EMIRATE_ID_COPY
                && $emirateIdType === AdnicEnum::EMIRATES_ID_CODE
            ) {
                return $sorted->take(2);
            }

            return $sorted->take(1);
        })->values();
    }

    public function getQuoteDocumentTypeCodessToUpload()
    {
        return collect([
            DocumentTypeCode::HEA_VISA => [
                'code' => DocumentTypeCode::HEA_VISA,
                'insurerDocCode' => '6',
                'insurerDocName' => 'Insured Visa Copy',
                'uploaded' => false,
            ],
            DocumentTypeCode::HEA_PAS => [
                'code' => DocumentTypeCode::HEA_PAS,
                'insurerDocCode' => '1',
                'insurerDocName' => 'Insured Passport',
                'uploaded' => false,
            ],
            DocumentTypeCode::HEA_EMIRATE_ID_COPY => [
                'code' => DocumentTypeCode::HEA_EMIRATE_ID_COPY,
                'insurerDocCode' => '3',
                'insurerDocName' => 'Emirates ID',
                'uploaded' => false,
            ],
            DocumentTypeCode::HEA_BIRTH_CERTIFICATE => [
                'code' => DocumentTypeCode::HEA_BIRTH_CERTIFICATE,
                'insurerDocCode' => '11',
                'insurerDocName' => 'Birth Certificate',
                'uploaded' => false,
            ],
            DocumentTypeCode::HEA_MEDICAL_APPLICATION_FORM => [
                'code' => DocumentTypeCode::HEA_MEDICAL_APPLICATION_FORM,
                'insurerDocCode' => '18',
                'insurerDocName' => 'Medical Application Form',
                'uploaded' => false,
            ],
            DocumentTypeCode::HEA_CUSTOMER_DUE_DILIGENCE => [
                'code' => DocumentTypeCode::HEA_CUSTOMER_DUE_DILIGENCE,
                'insurerDocCode' => '17',
                'insurerDocName' => 'Customer Due Diligence',
                'uploaded' => false,
            ],
        ]);
    }

    public function modifyEmirateDocument($quoteUuid): ?int
    {
        $result = null;

        $umafResponse = HealthUMAFResponse::where('quote_uuid', $quoteUuid)->first();

        if ($umafResponse === null) {
            LoggerService::info('Health UMAF response not found for quote', extra: [
                'quote_uuid' => $quoteUuid,
            ]);
        } else {
            $answers = $umafResponse->answers ?? [];
            if (! is_array($answers)) {
                LoggerService::info('Health UMAF response has no answers array', extra: [
                    'quote_uuid' => $quoteUuid,
                ]);
            } else {
                $typeOfEID = collect($answers)->filter(function ($answer) {
                    return ($answer['question_code'] ?? null) == 'typeOfEID';
                })->values()->first();

                if (! $typeOfEID) {
                    LoggerService::info('Type of EID not found');
                } elseif ($typeOfEID['answer_text'] == AdnicEnum::EMIRATES_ID_TEXT) {
                    $result = AdnicEnum::EMIRATES_ID_CODE;
                } elseif ($typeOfEID['answer_text'] == AdnicEnum::INSURED_EMIRATES_ID_APPLICATION_TEXT) {
                    $result = AdnicEnum::INSURED_EMIRATES_ID_APPLICATION_CODE;
                }
            }
        }

        return $result;
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
        $quoteType = QuoteTypes::HEALTH->value;
        // Ensure is_base_64 flag is set in data for proper handling
        $data['is_base_64'] = 1;
        $data['quote_uuid'] = $quote->uuid;
        $data['quote_type'] = $quoteType;
        $data['file_name'] = $originalName;
        $data['document_type_code'] = $documentCode;

        return $this->quoteDocumentService->uploadQuoteDocument($documentContent, $data, $quote);
    }

    /**
     * Map document types to Adnic document type codes
     */
    public function getInsurerDocCodeForHealth(string $documentType): ?string
    {
        return match ($documentType) {
            DocumentTypeCode::HEA_EMIRATE_ID_COPY => '3', // Emirates ID (Front side & Back side)
            DocumentTypeCode::HEA_INSURED_EMIRATES_ID_APPLICATION => '2', // Insured Emirates ID Application
            DocumentTypeCode::HEA_VISA => '6', // Visa
            DocumentTypeCode::HEA_PAS => '1', // Passport
            DocumentTypeCode::HEA_EID_FRONT => '4', // Emirates ID Front
            DocumentTypeCode::HEA_EID_BACK => '5', // Emirates ID Back
            DocumentTypeCode::HEA_BIRTH_CERTIFICATE => '11', // Birth Certificate
            DocumentTypeCode::HEA_MEDICAL_APPLICATION_FORM => '18', // Medical Application Form
            DocumentTypeCode::HEA_CUSTOMER_DUE_DILIGENCE => '17', // Customer Due Diligence
            default => null
        };
    }

    /**
     * Map document types to IMCRM document type codes
     */
    public function getDocTypeCodeForIMCRM(): array
    {
        return [
            AdnicEnum::INSURER_DOCUMENT_KEY_POLICY_DOCUMENT => DocumentTypeCode::POLC,
            AdnicEnum::INSURER_DOCUMENT_KEY_COMMISION_NOTE => DocumentTypeCode::TIRBB,
            AdnicEnum::INSURER_DOCUMENT_KEY_TAX_INVOICE => DocumentTypeCode::TI,
        ];
    }

    /**
     * Map insurer documents to IMCRM document type codes
     */
    public function getQuoteDocumentMappingForInsurerDocuments($documentType): ?string
    {
        return match ($documentType) {
            AdnicEnum::INSURER_DOCUMENT_KEY_POLICY_DOCUMENT => DocumentTypeCode::POLC,
            AdnicEnum::INSURER_DOCUMENT_KEY_COMMISION_NOTE => DocumentTypeCode::TIRBB,
            AdnicEnum::INSURER_DOCUMENT_KEY_TAX_INVOICE => DocumentTypeCode::TI,
            default => null
        };
    }
}
