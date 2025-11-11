<?php

namespace App\Services\OCR\Validators;

use App\Enums\DocumentTypeCode;

class CarValidator
{
    private $fieldsToVerify = [
        DocumentTypeCode::DRIVING_LICENSE => [
            'driver_license_expiry_date',
            'driver_license_expiry_place',
        ],
        DocumentTypeCode::EMIRATES_ID => [

        ],
        DocumentTypeCode::REGISTRATION_CARD_MULKIYA => [

        ],
    ];

    public function validate(array $ocrData, string $documentType): bool
    {
        foreach ($this->fieldsToVerify[$documentType] as $field) {
            if (empty($ocrData[$field])) {
                return false;
            }
        }

        return true;
    }
}
