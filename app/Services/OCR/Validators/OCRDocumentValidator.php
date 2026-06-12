<?php

namespace App\Services\OCR\Validators;

use App\Enums\CarRegistrationType;
use App\Enums\CarVehicleUse;
use App\Enums\DocumentTypeCode;
use App\Models\CarQuote;
use App\Models\CustomerInsured;
use App\Models\PassportVisaDetail;
use App\Models\QuoteDocument;
use App\Models\RegistrationCertificate;
use App\Models\VehicleDriverDetail;

class OCRDocumentValidator
{
    protected $quoteId;
    protected $quoteableType;

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
        'DRIVER_EMIRATES_ID_FIELDS' => [
            'driver_eid_number',
            'driver_name',
            'dob',
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
        'PASSPORT_FIELDS' => [
            'passport_number',
            'passport_country',
            'passport_expiry_date',
        ],
    ];

    public function __construct(int $quoteId, string $quoteableType)
    {
        $this->quoteId = $quoteId;
        $this->quoteableType = $quoteableType;
    }

    public function validateDLFields(string $documentTypeCode): bool
    {
        $fieldsToVerify = self::FIELDS_TO_VERIFY['DRIVING_LICENSE_FIELDS'];
        $vehicleDriverDetails = VehicleDriverDetail::where('quoteable_id', $this->quoteId)
            ->where('quoteable_type', $this->quoteableType)
            ->select($fieldsToVerify)
            ->first();

        // Check if all fields are not empty
        $result = $vehicleDriverDetails && empty(array_filter($fieldsToVerify,
            fn ($field) => empty($vehicleDriverDetails->$field)
        ));

        // Update ocr flag in quote document
        $this->updateQuoteDocument($this->quoteId, $documentTypeCode, $result);

        return $result;
    }

    public function validateEIDFields(string $documentTypeCode): bool
    {
        $vehicleDriverDetails = VehicleDriverDetail::where('quoteable_id', $this->quoteId)
            ->where('quoteable_type', $this->quoteableType)
            ->select('driver_gender')
            ->first();

        // Reminder:: Quote type id missing here - used forQuote
        $customerInsured = CustomerInsured::active()
            ->where('quote_request_id', $this->quoteId)
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
        $this->updateQuoteDocument($this->quoteId, $documentTypeCode, $result);

        return $result;
    }

    public function validateDriverEidFields(CarQuote $quote): bool
    {
        // Verify driver_eid_number in vehicle_driver_details
        $vehicleDriverDetails = VehicleDriverDetail::where('quoteable_id', $quote->id)
            ->where('quoteable_type', CarQuote::class)
            ->select('driver_eid_number')
            ->first();

        // Verify driver details in car_quote_request
        $carQuoteFields = ['driver_name', 'dob', 'nationality_id'];

        // Check if driver_eid_number exists in vehicle_driver_details
        $result = $vehicleDriverDetails && ! empty($vehicleDriverDetails->driver_eid_number);

        // Check if all required fields in car_quote are not empty (filters empty fields, result should be empty array = all filled)
        if ($quote->registration_type == CarRegistrationType::COMPANY && $quote->vehicle_use == CarVehicleUse::PRIVATE) {
            $result = $result && empty(array_filter($carQuoteFields, fn ($field) => empty($quote->$field)));
        }

        $this->updateQuoteDocument($quote->id, DocumentTypeCode::DRIVER_EMIRATES_ID, $result);

        return $result;
    }

    public function validateMulkiyaFields(string $documentTypeCode): bool
    {
        $vehicleDriverDetails = VehicleDriverDetail::where('quoteable_id', $this->quoteId)
            ->where('quoteable_type', $this->quoteableType)
            ->select(self::FIELDS_TO_VERIFY['VEHICLE_DRIVER_DETAIL_FIELDS'])
            ->first();

        $registrationCertificate = RegistrationCertificate::where('certificatable_id', $this->quoteId)
            ->where('certificatable_type', $this->quoteableType)
            ->select(self::FIELDS_TO_VERIFY['REGISTRATION_CERTIFICATE_FIELDS'])
            ->first();

        // Determine success flag
        $result = $vehicleDriverDetails && empty(array_filter(self::FIELDS_TO_VERIFY['VEHICLE_DRIVER_DETAIL_FIELDS'],
            fn ($field) => empty($vehicleDriverDetails->$field)));

        $result = $result && $registrationCertificate && empty(array_filter(self::FIELDS_TO_VERIFY['REGISTRATION_CERTIFICATE_FIELDS'],
            fn ($field) => empty($registrationCertificate->$field)));

        // Update ocr flag in quote document
        $this->updateQuoteDocument($this->quoteId, $documentTypeCode, $result);

        return $result;
    }

    public function validatePassportFields(string $documentTypeCode, int $customerMemberId = 0): bool
    {
        $fieldsToVerify = self::FIELDS_TO_VERIFY['PASSPORT_FIELDS'];

        $query = PassportVisaDetail::query()
            ->where('quoteable_id', $this->quoteId)
            ->where('quoteable_type', $this->quoteableType);

        if ($customerMemberId > 0) {
            $query->where('customer_member_id', $customerMemberId);
        } else {
            $query->whereNull('customer_member_id');
        }

        $passportVisaDetail = $query->select($fieldsToVerify)->first();

        $result = $passportVisaDetail && empty(array_filter(
            $fieldsToVerify,
            fn (string $field) => empty($passportVisaDetail->$field)
        ));

        $this->updateQuoteDocument($this->quoteId, $documentTypeCode, $result);

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
