<?php

namespace App\Services\OCR\Validators;

use App\Enums\DocumentTypeCode;

class OCRDocumentValidator
{
    private const FIELDS_TO_VERIFY = [
        DocumentTypeCode::DRIVING_LICENSE => [
            'driver_license_expiry_date',
            'driver_license_expiry_place',
        ],
        DocumentTypeCode::EMIRATES_ID => [
            'date_of_birth',
            'nationality',
            'name',

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
