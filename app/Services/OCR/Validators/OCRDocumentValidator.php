<?php

namespace App\Services\OCR\Validators;

use App\Enums\DocumentTypeCode;

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
            'driver_gender',
            'nationality_string',
        ],
        DocumentTypeCode::EMIRATES_ID => [
        ],
        DocumentTypeCode::REGISTRATION_CARD_MULKIYA => [
        ],
    ];

    public function validate(array $ocrData, string $documentType): bool
    {
        foreach (self::FIELDS_TO_VERIFY[$documentType] as $field) {
            if (empty($ocrData[$field])) {
                return false;
            }
        }

        return true;
    }
}
