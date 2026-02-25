<?php

namespace App\Services\PolicyIssuanceAutomation\Cyber;

use App\Enums\ApplicationStorageEnums;
use Carbon\Carbon;

class AwnicRequestBuilder
{
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
    public function buildIssuePolicyPayload($quote, $customer, $nationality, $planDetail, $splitPayment, $emirateOfRegistration): array
    {
        $emiratesIdNumber = null;
        if (isset($quote->latestInsured)) {
            $emiratesIdNumber = $quote->latestInsured['id_type'] == 'emiratesId' ? $quote->latestInsured['id_number'] : null;
        }

        $mobileNo = getAppStorageValueByKey(ApplicationStorageEnums::CHIEF_DEPUTY_OFFICER_MOBILE_NO, '');

        return [
            'CustName' => trim(($quote->first_name ?? '').' '.($quote->last_name ?? '')),
            'CustMobile' => $mobileNo,
            'CustEmail' => $quote->email,
            'CustEID' => str_replace('-', '', $emiratesIdNumber),
            'CustDOB' => $customer?->dob ? strtoupper(Carbon::parse($customer->dob)->format('d-M-Y')) : null,
            'CustAddress' => $emirateOfRegistration?->text ?? '',
            'CustCountryCode' => $nationality?->awni_country_code_number ?? null,
            'LimitOfLiability' => $planDetail->coverage ?? null,
            'PlanName' => $planDetail->planName ?? null,
            'PolStartDate' => strtoupper(Carbon::now()->format('d-M-Y')),
            'CustCode' => config('constants.AWNIC_API_BROKER_NO'),
            'BrokerCode' => config('constants.AWNIC_API_BROKER_NO'),
            'PaymentRefNo' => $splitPayment?->reference,
            'PartnerRefNo' => $quote->code,
        ];
    }

    /**
     * Build payload for upload documents API
     *
     * @param  mixed  $quote
     */
    public function buildUploadDocumentsPayload($quote, string $base64Content, string $documentType, string $documentName = 'Emirates_Id.png'): array
    {
        return [
            'QuoteRefNo' => $quote->insurer_quote_number,
            'DocCategory' => $documentType,
            'DocName' => $documentName,
            'DocContent' => $base64Content,
        ];
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
}
