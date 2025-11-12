<?php

namespace App\Services\OCR\Validators;

use App\Enums\DocumentTypeCode;
use App\Models\CustomerInsured;
use App\Models\QuoteDocument;
use App\Models\RegistrationCertificate;
use App\Models\VehicleDriverDetail;

class OCRDocumentValidator
{
    // If we segregate the fields by front/back, we will not know the uploaded
    // document is front or back.
    // Document can be full document or front or back.
    // One appraoch is to check all fields value in database on document uploading --OKKKKAYYY--
    // Another challenge is Jerin asked to delete only back document if any field in
    // back doc is null. We do not have identifier to identify front or back document.
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
            'nationality_id',
        ],
        'INSURED_FIELDS' => [
            'id_number',
            'first_name',
            'last_name',
            'dob',
            'nationality_id',
            'gender',
        ],
        'INSURED_KYC_FIELDS' => [
            'id_issuance_date',
            'id_expiry_date',
            'place_of_birth',
            'country_of_residence',
            'residential_address',
            'employer_company_name',
            'job_title',
        ],
        'VEHICLE_DRIVER_DETAIL_FIELDS' => [
            'vehicle_plate_number',
            'vehicle_plate_code',
            'first_registration_date',
            'vehicle_color',
            'vehicle_engine_number',
            'bank_name',
            'bank_loan',
            'traffic_code_number',
        ],
        'REGISTRATION_CERTIFICATE_FIELDS' => [
            'place_of_issue',
            'expiry_date',
            'owner',
            'nationality_id',
            'mortgage_by',
            'notes',
            'insured_with',
            'insurance_type',
            'model',
            'vehicle_class',
            'vehicle_type',
            'origin',
            'number_of_passengers',
            'gross_vehicle_weight',
            'empty_weight',
            'traffic_code_number',
        ],
    ];

    /*public function validate(array $ocrData, string $documentType, int $quoteDocumentId): bool
    {
        $result = ! array_filter(self::FIELDS_TO_VERIFY[$documentType], fn ($field) => empty($ocrData[$field]));

        QuoteDocument::where('id', $quoteDocumentId)->update([
            'is_ocr_processed' => $result,
        ]);

        return $result;
    }*/

    public function validateDLFields(int $quoteId): bool
    {
        $fieldsToVerify = self::FIELDS_TO_VERIFY[DocumentTypeCode::DRIVING_LICENSE];
        $vehicleDriverDetails = VehicleDriverDetail::where('quoteable_id', $quoteId)
            ->select($fieldsToVerify)
            ->first();

        // Check if all fields are not empty
        $result = $vehicleDriverDetails && empty(array_filter($fieldsToVerify,
            fn ($field) => empty($vehicleDriverDetails->$field)
        ));

        $this->updateQuoteDocument($quoteId, DocumentTypeCode::DRIVING_LICENSE, $result);

        return $result;
    }

    public function validateEIDFields(int $quoteId): bool
    {
        $vehicleDriverDetails = VehicleDriverDetail::where('quoteable_id', $quoteId)
            ->select('driver_gender')
            ->first();

        $customerInsured = CustomerInsured::where('quote_request_id', $quoteId)
            ->first();
        $insuredDetails = $customerInsured->insured;
        $insuredKycDetails = $insuredDetails->insuredKyc;

        // Determine success flag
        $result = $vehicleDriverDetails && $vehicleDriverDetails->driver_gender;
        $result = $result && $insuredDetails && empty(array_filter(self::FIELDS_TO_VERIFY['INSURED_FIELDS'],
            fn ($field) => empty($insuredDetails->$field)
        ));
        $result = $result && $insuredKycDetails && empty(array_filter(self::FIELDS_TO_VERIFY['INSURED_KYC_FIELDS'],
            fn ($field) => empty($insuredKycDetails->$field)
        ));

        $this->updateQuoteDocument($quoteId, DocumentTypeCode::EMIRATES_ID, $result);

        return $result;
    }

    public function validateMulkiyaFields(int $quoteId): bool
    {
        $vehicleDriverDetails = VehicleDriverDetail::where('quoteable_id', $quoteId)
            ->select(self::FIELDS_TO_VERIFY['VEHICLE_DRIVER_DETAIL_FIELDS'])
            ->first();

        $registrationCertificate = RegistrationCertificate::where('certificatable_id', $quoteId)
            ->select(self::FIELDS_TO_VERIFY['REGISTRATION_CERTIFICATE_FIELDS'])
            ->first();

        // Determine success flag
        $result = $vehicleDriverDetails && empty(array_filter(self::FIELDS_TO_VERIFY['VEHICLE_DRIVER_DETAIL_FIELDS'],
            fn ($field) => empty($vehicleDriverDetails->$field)));

        $result = $result && $registrationCertificate && empty(array_filter(self::FIELDS_TO_VERIFY['REGISTRATION_CERTIFICATE_FIELDS'],
            fn ($field) => empty($registrationCertificate->$field)));

        $this->updateQuoteDocument($quoteId, DocumentTypeCode::REGISTRATION_CARD_MULKIYA, $result);

        return $result;
    }

    private function updateQuoteDocument(int $quoteId, string $documentTypeCode, bool $result): void
    {
        QuoteDocument::where('quote_documentable_id', $quoteId)
            ->where('document_type_code', $documentTypeCode)->update([
                'is_ocr_processed' => $result,
            ]);
    }
}
