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
    public function buildIssuePolicyPayload($quote, $customer, $nationality, $planDetail, $payment, $splitPayment): array
    {
        // CustCode is hardcoded, i have tried different values but it is not working
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
            // 'PolStartDate' => $quote->policy_start_date ? strtoupper(Carbon::parse($quote->policy_start_date)->format('d-M-Y')) : strtoupper(\Carbon\Carbon::parse($payment->collection_date)->format('d-M-Y')),
            'PolStartDate' => strtoupper(Carbon::now()->format('d-M-Y')),
            'CustCode' => 150214,
            'BrokerCode' => 150214,
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
}

