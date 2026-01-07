<?php

namespace App\Services\OCR\Validators;

use App\Enums\DocumentTypeCode;
use App\Models\CarQuote;
use App\Models\CustomerInsured;
use App\Models\QuoteDocument;
use App\Models\RegistrationCertificate;
use App\Models\VehicleDriverDetail;

class OCRDocumentValidator
{
    private const FIELDS_TO_VERIFY = [
        'DRIVING_LICENSE_FIELDS' => [
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
            'first_registration_date',
            'vehicle_color',
            'vehicle_engine_number',
            'traffic_code_number',
        ],
        'REGISTRATION_CERTIFICATE_FIELDS' => [
            'place_of_issue',
            'expiry_date',
            'owner',
            'nationality_id',
            'mortgage_by',
            'model',
            'vehicle_type',
            'origin',
            'traffic_code_number',
        ],
    ];

    public function validateDLFields(int $quoteId): bool
    {
        $fieldsToVerify = self::FIELDS_TO_VERIFY['DRIVING_LICENSE_FIELDS'];
        $vehicleDriverDetails = VehicleDriverDetail::where('quoteable_id', $quoteId)
            ->where('quoteable_type', CarQuote::class)
            ->select($fieldsToVerify)
            ->first();

        // Check if all fields are not empty
        $result = $vehicleDriverDetails && empty(array_filter($fieldsToVerify,
            fn ($field) => empty($vehicleDriverDetails->$field)
        ));

        // Update ocr flag in quote document
        $this->updateQuoteDocument($quoteId, DocumentTypeCode::DRIVING_LICENSE, $result);

        return $result;
    }

    public function validateEIDFields(int $quoteId): bool
    {
        $vehicleDriverDetails = VehicleDriverDetail::where('quoteable_id', $quoteId)
            ->where('quoteable_type', CarQuote::class)
            ->select('driver_gender')
            ->first();

        // Reminder:: Quote type id missing here - used forQuote
        $customerInsured = CustomerInsured::active()
            ->where('quote_request_id', $quoteId)
            ->first();

        if (! $customerInsured) {
            return false;
        }

        // Retrieve insured details
        $insuredDetails = $customerInsured->insured;

        if (! $insuredDetails) {
            return false;
        }

        // Retrieve insured KYC details
        $insuredKycDetails = $insuredDetails->insuredKyc;

        // Determine success flag
        $result = $vehicleDriverDetails && $vehicleDriverDetails->driver_gender;
        $result = $result && $insuredDetails && empty(array_filter(self::FIELDS_TO_VERIFY['INSURED_FIELDS'],
            fn ($field) => empty($insuredDetails->$field)
        ));
        $result = $result && $insuredKycDetails && empty(array_filter(self::FIELDS_TO_VERIFY['INSURED_KYC_FIELDS'],
            fn ($field) => empty($insuredKycDetails->$field)
        ));

        // Update ocr flag in quote document
        $this->updateQuoteDocument($quoteId, DocumentTypeCode::EMIRATES_ID, $result);

        return $result;
    }

    public function validateMulkiyaFields(int $quoteId): bool
    {
        $vehicleDriverDetails = VehicleDriverDetail::where('quoteable_id', $quoteId)
            ->where('quoteable_type', CarQuote::class)
            ->select(self::FIELDS_TO_VERIFY['VEHICLE_DRIVER_DETAIL_FIELDS'])
            ->first();

        $registrationCertificate = RegistrationCertificate::where('certificatable_id', $quoteId)
            ->where('certificatable_type', CarQuote::class)
            ->select(self::FIELDS_TO_VERIFY['REGISTRATION_CERTIFICATE_FIELDS'])
            ->first();

        // Determine success flag
        $result = $vehicleDriverDetails && empty(array_filter(self::FIELDS_TO_VERIFY['VEHICLE_DRIVER_DETAIL_FIELDS'],
            fn ($field) => empty($vehicleDriverDetails->$field)));

        $result = $result && $registrationCertificate && empty(array_filter(self::FIELDS_TO_VERIFY['REGISTRATION_CERTIFICATE_FIELDS'],
            fn ($field) => empty($registrationCertificate->$field)));

        // Update ocr flag in quote document
        $this->updateQuoteDocument($quoteId, DocumentTypeCode::REGISTRATION_CARD_MULKIYA, $result);

        return $result;
    }

    private function updateQuoteDocument(int $quoteId, string $documentTypeCode, bool $result): void
    {
        // Update ocr process flag against all documents (of same type i.e. CEID/CAR_MULKIY)
        QuoteDocument::where('quote_documentable_id', $quoteId)
            ->where('document_type_code', $documentTypeCode)->update([
                'is_ocr_processed' => $result,
            ]);
    }
}
