<?php

namespace App\Services\PolicyIssuanceAutomation\Cyber;

use Carbon\Carbon;

class AwnicRequestBuilder
{
    /**
     * Build payload for issue policy API
     *
     * @param mixed $quote
     * @param mixed $customer
     * @param mixed $nationality
     * @param mixed $planDetail
     * @param mixed $payment
     * @param mixed $splitPayment
     * @return array
     */
    public function buildIssuePolicyPayload($quote, $customer, $nationality, $planDetail, $splitPayment): array
    {
        return [
            'CustName' => trim(($quote->first_name ?? '') . ' ' . ($quote->last_name ?? '')),
            'CustMobile' => $quote->mobile_no,
            'CustEmail' => $quote->email,
            'CustEID' => str_replace('-', '', $customer->emirates_id_number ?? "784200012345671"),
            'CustDOB' => $customer->dob ? strtoupper(Carbon::parse($customer->dob)->format('d-M-Y')) : null,
            'CustAddress' => $quote->company_address ?? "abc address",
            'CustCountryCode' => $nationality?->awni_country_code ?? null,
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
     * @param mixed $quote
     * @param string $base64Content
     * @param string $documentType
     * @param string $documentName
     * @return array
     */
    public function buildUploadDocumentsPayload($quote, string $base64Content, string $documentType, string $documentName = 'Emirates_Id.png'): array
    {
        return [
            "QuoteRefNo" => $quote->insurer_quote_number,
            "DocCategory" => $documentType,
            "DocName" => $documentName,
            "DocContent" => $base64Content,
        ];
    }

    /**
     * Build payload for download document API
     *
     * @param mixed $docId
     * @return array
     */
    public function buildDownloadDocumentPayload($docId): array
    {
        return [
            "docId" => $docId,
        ];
    }

    /**
     * Build headers required for policy issuance API
     *
     * @return array
     */
    public function buildIssuePolicyHeaders(): array
    {
        return [
            'TP-Payment-Key' => 'TP_PAYMENT',
            'Accept' => 'application/json',
        ];
    }
}

