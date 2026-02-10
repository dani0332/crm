<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Health\Adnic;

use App\Enums\AdnicEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\PolicyIssuanceEnum;

class AdnicRequestBuilder
{
    public function __construct(
        private AdnicHttpClient $httpClient,
    ) {}

    /**
     * Get length of Emirates ID value based on its data type (string length or array count).
     */
    private function getEmiratesIdLength(mixed $value): int
    {
        if ($value === null) {
            return '';
        }

        return match (true) {
            is_string($value) => strlen($value),
            is_array($value) => count($value),
            default => 0,
        };
    }

    /**
     * Return Emirates ID string if its length is at least $minLength, otherwise empty string.
     */
    private function emiratesIdIfMinLength(mixed $emiratesId, int $minLength): string
    {
        $length = $this->getEmiratesIdLength($emiratesId);

        return $length >= $minLength ? (string) $emiratesId : '';
    }

    /**
     * Return Emirates ID string if its length is greater than 0 and less than $maxLength, otherwise empty string.
     */
    private function emiratesIdIfMaxLengthExclusive(mixed $emiratesId, int $maxLength): string
    {
        $length = $this->getEmiratesIdLength($emiratesId);

        return $length > 0 && $length < $maxLength ? (string) $emiratesId : '';
    }

    /**
     * Build payload for issue policy API
     *
     * @param  mixed  $quote
     * @param  mixed  $customer
     * @param  mixed  $nationality
     * @param  mixed  $planDetail
     * @param  mixed  $payment
     * @param  mixed  $splitPayment
     */
    public function buildIssuePolicyPayload($quote, $process, $healthInsurerRequestResponse, $splitPayment): array
    {
        $chargeId = $splitPayment?->paymentCharges?->transaction_id ?? null;

        $healthUmafDetails = $quote->healthUmafResponse;
        $healthUmafQuestionCollection = collect($healthUmafDetails?->answers);
        // Questions
        $inceptionDate = $healthUmafQuestionCollection->where('question_code', 'inceptionDate')->first()['answer_text'] ?? null;
        $currentlyPregnant = $healthUmafQuestionCollection->where('question_code', 'currentlyPregnant')->first()['answer_text'] ?? null;
        $emiratesId = $healthUmafQuestionCollection->where('question_code', 'emiratesId')->first()['answer_text'] ?? null;
        $passportNumber = $healthUmafQuestionCollection->where('question_code', 'passportNumber')->first()['answer_text'] ?? null;
        $previouslyCovered = $healthUmafQuestionCollection->where('question_code', 'adnicInsured')->first()['answer_text'] ?? null;
        $sponsorCategory = $healthUmafQuestionCollection->where('question_code', 'sponsorCategory')->first()['answer_text'] ?? null;
        $visaFileNumber = $healthUmafQuestionCollection->where('question_code', 'visaFileNumber')->first()['answer_text'] ?? null;
        $industry = $healthUmafQuestionCollection->where('question_code', 'industry')->first()['answer_text'] ?? null;
        // $visaType = $healthUmafQuestionCollection->where('question_code', 'visaType')->first()['answer_text'] ?? null;
        $visaType = AdnicEnum::VISA_TYPE_EXISTING_VISA_HOLDER;
        $customerClassification = AdnicEnum::CUSTOMER_CLASSIFICATION_NATURAL_PERSONS; // FIX Value
        $memberCategory = $sponsorCategory; // Member Category is same as Sponsor Category

        $healthInsurerRequest = json_decode($healthInsurerRequestResponse->request);
        $healthInsurerResponse = json_decode($healthInsurerRequestResponse->response);

        $uploadDocumentsResponse = $process->policyIssuanceLogs()->where([
            'step' => AdnicEnum::STEP_UPLOAD_DOCUMENTS,
            'status' => PolicyIssuanceEnum::SUCCESS_STATUS,
        ])->get();

        $insuredInfoDetails =
        $insuredInfoArray = [];

        if ($healthInsurerRequest && isset($healthInsurerRequest->InsuredInfo)) {
            $insuredInfoDetails = $healthInsurerRequest->InsuredInfo;
        }

        foreach ($insuredInfoDetails as $insuredMember) {
            $insuredInfoArray[] = [
                'MemberSeqNo' => $insuredMember->MemberSeqNo,
                'Salutation' => $insuredMember->Salutation,
                'MemberName' => $insuredMember->MemberName,
                'DateOfBirth' => $insuredMember->DateOfBirth ? date('d-m-Y', strtotime($insuredMember->DateOfBirth)) : null,
                'Gender' => $insuredMember->Gender,
                'Relation' => $insuredMember->Relation,
                'Height' => $insuredMember->Height,
                'Weight' => $insuredMember->Weight,
                'MaritalStatus' => $insuredMember->MaritalStatus,
                'DisclaimerSelected' => $insuredMember->DisclaimerSelected,
                'Occupation' => $industry ?? AdnicEnum::OCCUPATION_OTHER,
                'LoadingInfo' => [
                    'LoadingType' => AdnicEnum::LOADING_TYPE,
                    'LoadingTypeValue' => AdnicEnum::LOADING_VALUE,
                    'LoadingAmount' => AdnicEnum::LOADING_AMOUNT,
                ],
                'QuestionnarieInfo' => (array) $insuredMember->QuestionnarieInfo,
                'PregnantStatus' => $currentlyPregnant ?? AdnicEnum::NO,
                'PreviouslyCovered' => $previouslyCovered ?? AdnicEnum::NO,
                'EmiratesId' => $this->emiratesIdIfMinLength($emiratesId, 15),
                'EidApplicationNo' => $this->emiratesIdIfMaxLengthExclusive($emiratesId, 15),
                'EntryPermitNoOrFileNo' => $visaFileNumber ?? '',
                'CustomerClassification' => $customerClassification ?? '1', // 1 => Natural persons, 2 => Legal Persons- Corporates // TODO : Need to check this
                'MemberCategory' => $memberCategory ?? '',
                'SalaryType' => $this->mappingSalaryBand($quote->salary_band_id ?? null),
                'Commission' => AdnicEnum::NO, // Optional Field, set as default value
                'VisaType' => $visaType ?? '',
                'City' => $healthInsurerRequest->SponsorInfo->PreviousVisaEmirate,
                'Nationality' => AdnicEnum::NATIONALITY_ID_EMIRATES_ID, // Emirates ID is the default nationality
                'PassportNo' => $passportNumber ?? '',
                'UIDNo' => $emiratesId ?? '',
                'WorkLocation' => $healthInsurerRequest->SponsorInfo->PreviousVisaEmirate,
                'ResidenceLocation' => $healthInsurerRequest->SponsorInfo->PreviousVisaEmirate,
                'Industry' => $industry ?? '',
                'DocumentInfo' => $this->uploadedDocumentsInfo($uploadDocumentsResponse),
                'PreviousVisaEmirate' => $insuredMember?->PreviousVisaEmirate,
            ];
        }

        return [
            'PartnerInfo' => [
                'PartnerId' => $this->httpClient->getPartnerId(),
            ],
            'SponsorInfo' => [
                'SponserType' => $healthInsurerRequest?->SponsorInfo?->SponserType,
                'SponserName' => $healthInsurerRequest?->SponsorInfo?->SponserName,
                'MobileNo' => AdnicEnum::RESPONSIBLE_PERSON_DEFAULT_MOBILE,
                'EmailId' => AdnicEnum::RESPONSIBLE_PERSON_DEFAULT_EMAIL,
                'Address' => $quote->emirate?->text ?? '',
                'DateOfBirth' => $quote->dob ? date('d-m-Y', strtotime($quote->dob)) : null,
                'Gender' => $this->mappingGender($quote->gender ?? null),
                'Nationality' => $this->mappingNationality($quote->nationality?->name ?? null), // Need to check, it's pass as null
                'SalaryType' => $this->mappingSalaryBand($quote->salary_band_id ?? null), // TODO:: Some attributes need to be created
                'EmiratesId' => $emiratesId ?? '',
                'City' => $healthInsurerRequest?->SponsorInfo?->PreviousVisaEmirate,
                'SponserCategory' => $sponsorCategory ?? '',
                'MaritalStatus' => $this->mappingMaritalStatus($quote->marital_status_id ?? null),
                'PassportNo' => $passportNumber ?? '',
                'UIDNo' => $emiratesId ?? '',
                'MemberCategory' => $memberCategory ?? '',
                'VisaType' => $visaType ?? '',
                'PreviousVisaEmirate' => $healthInsurerRequest?->SponsorInfo?->PreviousVisaEmirate,
                'CustomerClassification' => $customerClassification ?? '1',
            ],
            'QuoteInfo' => [
                'PartnerReferenceNo' => $this->httpClient->getPartnerReferenceNo(),
                'PartnerPremium' => $healthInsurerRequest?->QuoteInfo?->PartnerPremium,
                'QuotationNo' => $healthInsurerResponse?->QuoteInfo?->QuotationNo,
                'VisaEmirate' => $healthInsurerRequest->SponsorInfo->PreviousVisaEmirate,
                'ProductType' => $healthInsurerRequest?->QuoteInfo?->ProductType,
                'PolicyType' => $healthInsurerRequest?->QuoteInfo?->PolicyType, // Individual received hora hai mapping mai F ana
                'PlanType' => $healthInsurerRequest?->QuoteInfo?->PlanType,
                'DeductibleAmount' => $healthInsurerRequest?->QuoteInfo?->DeductibleAmount,
                'CoInsurancePercent' => $healthInsurerRequest?->QuoteInfo?->CoInsurancePercent,
                'DentalCover' => $healthInsurerRequest?->QuoteInfo?->DentalCover,
                'OpticalCover' => $healthInsurerRequest?->QuoteInfo?->OpticalCover,
                'SalaryBand' => $healthInsurerRequest?->QuoteInfo?->SalaryBand, // Optional Field
                'NoOfPersonsToInsure' => $healthInsurerRequest?->QuoteInfo?->NoOfPersonsToInsure,
            ],
            'InsuredInfo' => $insuredInfoArray,
            'PolicyInfo' => [
                'PolicyStartDate' => $inceptionDate ? date('d-m-Y', strtotime($inceptionDate)) : null,
                'PaymentType' => AdnicEnum::PAYMENT_TYPE,
                'PaymentRefNo' => $chargeId,
            ],
        ];
    }

    /**
     * Build payload for upload documents API
     *
     * @param  mixed  $quote
     */
    public function buildUploadDocumentsPayload(string $base64Content, $healthInsurerResponse, $insuredMember, $insurerDocCode, $quoteDocument): array
    {
        return [
            'PartnerInfo' => [
                'PartnerId' => $this->httpClient->getPartnerId(),
            ],
            'DocumentInfo' => [
                'QuotationNo' => $healthInsurerResponse?->QuoteInfo?->QuotationNo,
                'MemberSeqNo' => $insuredMember?->MemberSeqNo,
                'DocumentType' => $insurerDocCode,
                'DocumentName' => $quoteDocument->original_name ?? $quoteDocument->doc_name,
                'DocumentUploadDate' => now()->toISOString(),
                'IsDocumentValidated' => 'Y',
                'DocumentContent' => $base64Content,
            ],
        ];
    }

    /**
     * Build payload for download document API
     *
     * @param  mixed  $docId
     */
    public function buildDownloadDocumentPayload($generatePolicyResponse, $docId): array
    {
        return [
            'PartnerInfo' => [
                'PartnerId' => $this->httpClient->getPartnerId(),
            ],
            'PolicyDocumentInfo' => [
                'PartnerReferenceNo' => $this->httpClient->getPartnerReferenceNo(),
                'QuotationNo' => $generatePolicyResponse?->QuoteInfo?->QuotationNo,
                'PolicyNo' => $generatePolicyResponse?->PolicyInfo?->PolicyNo,
                'DocumentId' => $docId,
            ],
        ];
    }

    private function uploadedDocumentsInfo($uploadDocumentsResponse): array
    {
        $documentInfo = [];
        foreach ($uploadDocumentsResponse as $document) {
            $response = json_decode($document->response);

            $responseData = data_get($response, 'data');

            $documentInfo[] = [
                'DocumentType' => data_get($responseData, 'DocumentInfo.DocumentType'),
                'DocumentId' => data_get($responseData, 'DocumentInfo.DocumentId'),
            ];
        }

        return $documentInfo;
    }

    // ----------------------------- ADNIC Payload Mappings ----------------------------- //

    private function mappingSalaryBand($salaryBand): ?int
    {
        $salaryBandMapping = [
            1 => 1, // 4000 and less
            2 => 2, // More than 4000
        ];

        return $salaryBandMapping[$salaryBand] ?? null;
    }

    private function mappingNationality($nationality): ?int
    {
        if (is_null($nationality)) {
            return null;
        }

        $nationalityMapping = [
            'EMIRATI' => 1,
            'JORDANIAN' => 11,
            'LEBANESE' => 12,
            'SYRIAN' => 13,
            'EGYPTIAN' => 14,
            'ANTIGUAN' => 100,
            'ARGENTINE' => 101,
            'AUSTRIAN' => 102,
            'AZERBAIJANI' => 103,
            'BAHAMIAN' => 104,
            'BARBADIAN' => 105,
            'BELARUSIAN' => 106,
            'BELGIAN' => 107,
            'BELIZEAN' => 108,
            'BENINESE' => 109,
            'BHUTANESE' => 110,
            'BOLIVIAN' => 111,
            'MOTSWANA' => 112,
            'BRUNEIAN' => 113,
            'BULGARIAN' => 114,
            'BURKINABE' => 115,
            'BURUNDIAN' => 116,
            'CAMBODIAN' => 117,
            'CAMEROONIAN' => 118,
            'CAPE VERDEAN' => 119,
            'CENTRAL AFRICAN' => 120,
            'CHADIAN' => 121,
            'CHILEAN' => 122,
            'CHINESE' => 123,
            'COLOMBIAN' => 124,
            'COMORAN' => 125,
            'COOK ISLANDER' => 127,
            'COSTA RICAN' => 128,
            'IVORIAN' => 129,
            'CROATIAN' => 130,
            'CUBAN' => 131,
            'CYPRIOT' => 132,
            'CZECH' => 133,
            'CONGOLESE' => 135,
            'DANISH' => 136,
            'DOMINICAN1' => 137,
            'DOMINICAN' => 138,
            'ECUADORIAN' => 139,
            'SALVADORAN' => 140,
            'EQUATOGUINEAN' => 141,
            'ESTONIAN' => 142,
            'FIJIAN' => 143,
            'GABONESE' => 144,
            'GAMBIAN' => 145,
            'GEORGIAN' => 146,
        ];

        return $nationalityMapping[ucwords($nationality)] ?? null;
    }

    private function mappingGender($gender): string
    {
        if (in_array($gender, [
            GenericRequestEnum::FEMALE,
            strtolower(GenericRequestEnum::FEMALE),
            GenericRequestEnum::FEMALE_SHORT_VALUE,
            GenericRequestEnum::FEMALE_SINGLE,
            GenericRequestEnum::FEMALE_SINGLE_VALUE,
            GenericRequestEnum::FEMALE_MARRIED,
            GenericRequestEnum::FEMALE_MARRIED_VALUE,
        ])) {
            return 'F';
        }

        return 'M';
    }

    private function mappingMaritalStatus($maritalStatus): ?int
    {
        $maritalStatusMapping = [
            1 => 1, // Single
            2 => 2, // Married
            3 => 4, // Widowed
            4 => 3, // Divorced
        ];

        return $maritalStatusMapping[$maritalStatus] ?? null;
    }

    private function getFullName($member): string
    {
        $firstName = $member->first_name ?? '';
        $lastName = $member->last_name ?? '';

        return trim($firstName.' '.$lastName);
    }

    private function getRelationCode($relation): string
    {
        $relationMapping = [
            'self' => 'S',
            'spouse' => 'SP',
            'child' => 'C',
            'parent' => 'P',
        ];

        return $relationMapping[strtolower($relation)] ?? 'S';
    }

    private function getSalutation($gender): string
    {
        if (in_array($gender, [GenericRequestEnum::FEMALE, strtolower(GenericRequestEnum::FEMALE), GenericRequestEnum::FEMALE_SHORT_VALUE, GenericRequestEnum::FEMALE_SINGLE, GenericRequestEnum::FEMALE_SINGLE_VALUE])) {
            return 'Ms';
        }
        if (in_array($gender, [GenericRequestEnum::FEMALE_MARRIED, GenericRequestEnum::FEMALE_MARRIED_VALUE])) {
            return 'Mrs';
        }

        return 'Mr';
    }

    private function getProductType($quote): string
    {
        // TODO:: Get this product type from the Health Plan Name, Extract the first word and convert it to uppercase
        return 'SHIFA';
    }

    private function getPlanType($quote): string
    {
        // TODO:: Get this plan type from the Health Plan Name, Extract the first word and convert it to uppercase
        return 'GO';
    }

}
