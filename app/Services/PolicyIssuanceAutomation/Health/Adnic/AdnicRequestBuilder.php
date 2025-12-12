<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Health\Adnic;

use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicHttpClient;
use Carbon\Carbon;
use App\Models\HealthUMAFResponses;
use App\Enums\AdnicEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\GenericRequestEnum;

class AdnicRequestBuilder
{
    public function __construct(
        private AdnicHttpClient $httpClient,
    ) {}

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

        $UMAFDetails = HealthUMAFResponses::where('quote_uuid', $quote->uuid)->first(); 

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
                'Occupation' => $insuredMember->Occupation,
                'LoadingInfo' => [
                    'LoadingType' => AdnicEnum::LOADING_TYPE,
                    'LoadingTypeValue' => AdnicEnum::LOADING_VALUE,
                    'LoadingAmount' => AdnicEnum::LOADING_AMOUNT,
                ],
                'QuestionnarieInfo' => (array) $insuredMember->QuestionnarieInfo,
                'PregnantStatus' => $UMAFDetails?->PregnantStatus ?? AdnicEnum::NO,
                'PreviouslyCovered' => $UMAFDetails?->PreviouslyCovered ?? AdnicEnum::NO,
                'EmiratesId' => $UMAFDetails?->EmiratesId ?? '',
                'EntryPermitNoOrFileNo' => $UMAFDetails?->EntryPermitNoOrFileNo ?? '',
                'CustomerClassification' => $UMAFDetails?->CustomerClassification ?? '1', // 1 => Natural persons, 2 => Legal Persons- Corporates
                'MemberCategory' => $UMAFDetails?->MemberCategory ?? '',
                'SalaryType' => $this->mappingSalaryBand($quote->salary_band_id ?? null),
                'Commission' => AdnicEnum::NO, // Optional Field, set as default value
                'VisaType' => $UMAFDetails?->VisaType ?? '',
                'City' => $healthInsurerRequest->SponsorInfo->PreviousVisaEmirate,
                'Nationality' => $this->mappingNationality($quote->nationality?->name ?? null),
                'PassportNo' => $UMAFDetails?->PassportNo ?? '',
                'UIDNo' => $UMAFDetails?->EmiratesId ?? '',
                'WorkLocation' => $healthInsurerRequest->SponsorInfo->PreviousVisaEmirate,
                'ResidenceLocation' => $healthInsurerRequest->SponsorInfo->PreviousVisaEmirate,
                'Industry' => $UMAFDetails?->Industry ?? '',
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
                'EmiratesId' => $UMAFDetails?->EmiratesId ?? '',
                'City' => $healthInsurerRequest?->SponsorInfo?->PreviousVisaEmirate,
                'SponserCategory' => $UMAFDetails?->SponserCategory ?? '',
                'MaritalStatus' => $this->mappingMaritalStatus($quote->marital_status_id ?? null),
                'PassportNo' => $UMAFDetails?->PassportNo ?? '',
                'UIDNo' => $UMAFDetails?->EmiratesId ?? '',
                'MemberCategory' => $UMAFDetails?->MemberCategory ?? '',
                'VisaType' => $UMAFDetails?->VisaType ?? '',
                'PreviousVisaEmirate' => $healthInsurerRequest?->SponsorInfo?->PreviousVisaEmirate,
                'CustomerClassification' => $UMAFDetails?->CustomerClassification ?? '1', // 1 => Natural persons, 2 => Legal Persons- Corporates
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
                'PolicyStartDate' => $quote->policy_start_date ? date('d-m-Y', strtotime($quote->policy_start_date)) : null,
                'PaymentType' => AdnicEnum::PAYMENT_TYPE,
                'PaymentRefNo' => $chargeId,
            ],
        ];;
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
        ];;
    }

    /**
     * Build payload for download document API
     *
     * @param  mixed  $docId
     */
    public function buildDownloadDocumentPayload($docId): array
    {
        return [
            'docId' => $docId,
        ];
    }

    /**
     * Build headers required for policy issuance API
     */
    public function buildIssuePolicyHeaders(): array
    {
        return [
            'TP-Payment-Key' => 'TP_PAYMENT',
            'Accept' => 'application/json',
        ];
    }

    
    private function uploadedDocumentsInfo($uploadDocumentsResponse): array
    {
        $documentInfo = [];
        foreach ($uploadDocumentsResponse as $document) {
            $responseData = json_decode($document->response);

            if (! isset($responseData->DocumentInfo) ||
                is_null($responseData->DocumentInfo->DocumentType) ||
                is_null($responseData->DocumentInfo->DocumentId)) {
                continue;
            }

            $documentInfo[$responseData->memberSeqNo][] = [
                'DocumentType' => $responseData->DocumentInfo->DocumentType,
                'DocumentId' => $responseData->DocumentInfo->DocumentId,
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
