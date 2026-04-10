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
            return 0;
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
    private function emiratesIdIfMaxLengthExclusive(mixed $emiratesId, int $maxLength)
    {
        $length = $this->getEmiratesIdLength($emiratesId);

        return $length > 0 && $length < $maxLength ? (string) $emiratesId : null;
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
        $uidNo = str_replace('-', '', $emiratesId ?? '');
        $passportNumber = $healthUmafQuestionCollection->where('question_code', 'passportNumber')->first()['answer_text'] ?? null;
        $previouslyCovered = $healthUmafQuestionCollection->where('question_code', 'adnicInsured')->first()['answer_text'] ?? null;
        $sponsorCategory = AdnicEnum::SPONSER_CATEGORY_UAE;
        $visaFileNumber = $healthUmafQuestionCollection->where('question_code', 'visaFileNumber')->first()['answer_text'] ?? null;
        $industry = $healthUmafQuestionCollection->where('question_code', 'industry')->first()['answer_text'] ?? null;
        $visaType = AdnicEnum::VISA_TYPE_EXISTING_VISA_HOLDER;
        $customerClassification = AdnicEnum::CUSTOMER_CLASSIFICATION_NATURAL_PERSONS; // FIX Value
        $memberCategory = AdnicEnum::MEMBER_CATEGORY_DUBAI_RESIDENCY; // Member Category is same as Sponsor Category

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
                'Occupation' => ! empty($industry) ? $industry : AdnicEnum::OCCUPATION_OTHER,
                'LoadingInfo' => [
                    'LoadingType' => AdnicEnum::LOADING_TYPE,
                    'LoadingTypeValue' => AdnicEnum::LOADING_VALUE,
                    'LoadingAmount' => AdnicEnum::LOADING_AMOUNT,
                ],
                'QuestionnarieInfo' => (array) $insuredMember->QuestionnarieInfo,
                'PregnantStatus' => $currentlyPregnant ?? AdnicEnum::NO,
                'PreviouslyCovered' => $previouslyCovered ?? AdnicEnum::NO,
                'EmiratesId' => $this->emiratesIdIfMinLength($emiratesId, 15),
                'EidApplicationNo' => $this->emiratesIdIfMaxLengthExclusive($emiratesId, 15) ?? null,
                'EntryPermitNoOrFileNo' => $visaFileNumber ?? '',
                'CustomerClassification' => $customerClassification ?? '1', // 1 => Natural persons, 2 => Legal Persons- Corporates
                'MemberCategory' => $memberCategory ?? '',
                'SalaryType' => $this->mappingSalaryBand($quote->salary_band_id ?? null),
                'Commission' => AdnicEnum::NO, // Optional Field, set as default value
                'VisaType' => $visaType ?? '',
                'City' => $healthInsurerRequest?->SponsorInfo?->PreviousVisaEmirate ?? '',
                'Nationality' => AdnicEnum::NATIONALITY_ID_EMIRATES_ID, // Emirates ID is the default nationality
                'PassportNo' => $passportNumber ?? '',
                'UIDNo' => $uidNo ?? '',
                'WorkLocation' => AdnicEnum::DUBAI_RESIDENCY,
                'ResidenceLocation' => AdnicEnum::DUBAI_RESIDENCY,
                'Industry' => ! empty($industry) ? $industry : AdnicEnum::OCCUPATION_OTHER,
                'DocumentInfo' => $this->uploadedDocumentsInfo($uploadDocumentsResponse, $insuredMember->MemberSeqNo),
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
                'MobileNo' => $quote?->mobile_no ?? '',
                'EmailId' => $quote?->email ?? '',
                'Address' => $quote->emirate?->text ?? '',
                'DateOfBirth' => $quote->dob ? date('d-m-Y', strtotime($quote->dob)) : null,
                'Gender' => $this->mappingGender($quote->gender ?? null),
                'Nationality' => AdnicEnum::NATIONALITY_ID_EMIRATES_ID, // Emirates ID is the default nationality
                'SalaryType' => $this->mappingSalaryBand($quote->salary_band_id ?? null),
                'EmiratesId' => $emiratesId ?? '',
                'City' => $healthInsurerRequest?->SponsorInfo?->PreviousVisaEmirate,
                'SponserCategory' => $sponsorCategory ?? '',
                'MaritalStatus' => $this->mappingMaritalStatus($quote->marital_status_id ?? data_get($insuredInfoArray, '0.MaritalStatus')),
                'PassportNo' => $passportNumber ?? '',
                'UIDNo' => $uidNo ?? '',
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

    private function uploadedDocumentsInfo($uploadDocumentsResponse, $memberSeqNo): array
    {
        $documentInfo = [];
        foreach ($uploadDocumentsResponse as $document) {
            $response = json_decode($document->response);

            $responseData = data_get($response, 'data');
            $responseMemberSeqNo = data_get($response, 'MemberSeqNo');

            // Only include documents for this specific member
            if ($responseMemberSeqNo == $memberSeqNo) {
                $documentInfo[] = [
                    'DocumentType' => data_get($responseData, 'DocumentInfo.DocumentType'),
                    'DocumentId' => data_get($responseData, 'DocumentInfo.DocumentId'),
                ];
            }
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
}
