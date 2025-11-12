<?php

namespace App\Services\OCR\Validators;

use App\Enums\DocumentTypeCode;
use App\Models\QuoteDocument;

class OCRDocumentValidator
{
    private const FIELDS_TO_VERIFY = [
        DocumentTypeCode::DRIVING_LICENSE => [
            'driver_license_number',
            'driver_license_issue_date',
            'driver_license_expiry_date',
            'driver_license_issue_place',
            'traffic_code_number',
            'driver_first_name',
            'driver_last_name',
            'driver_dob',
            'nationality_string',
        ],
        DocumentTypeCode::EMIRATES_ID => [
        ],
        DocumentTypeCode::REGISTRATION_CARD_MULKIYA => [
        ],
    ];

    public function validate(array $ocrData, string $documentType, int $quoteDocumentId): bool
    {
        $result = ! array_filter(self::FIELDS_TO_VERIFY[$documentType], fn ($field) => empty($ocrData[$field]));

        QuoteDocument::where('id', $quoteDocumentId)->update([
            'is_ocr_processed' => $result,
        ]);

        return $result;
    }
}
