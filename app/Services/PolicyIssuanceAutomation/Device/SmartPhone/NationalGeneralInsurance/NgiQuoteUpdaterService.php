<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance;

use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\Payment;
use App\Models\PaymentSplits;
use Carbon\Carbon;

class NgiQuoteUpdaterService
{
    /**
     * Update quote from CreatePolicyFromQuote API response
     *
     * @param mixed $quote
     * @param object $createPolicyResult
     * @return void
     */
    public function updateQuoteFromCreatePolicyResponse($quote, $createPolicyResult): void
    {
        $quote->update([
            'policy_number' => $createPolicyResult?->policy_no ?? null,
            'policy_issuance_date' => Carbon::now(),
            'policy_start_date' => $createPolicyResult?->policy_start_dt
                ? Carbon::parse($createPolicyResult->policy_start_dt)->format('Y-m-d')
                : $quote->policy_start_date,
            'policy_expiry_date' => $createPolicyResult?->policy_end_dt
                ? Carbon::parse($createPolicyResult->policy_end_dt)->format('Y-m-d')
                : $quote->policy_expiry_date,
            'quote_status_id' => QuoteStatusEnum::PolicyIssued,
            'policy_issuance_status_id' => PolicyIssuanceStatusEnum::PolicyIssued,
            'quote_status_date' => now(),
        ]);
    }

    /**
     * Update quote from GetPolicyDocuments API response
     *
     * @param mixed $quote
     * @param object $policyDocumentsResult
     * @return void
     */
    public function updateQuoteFromPolicyDocumentsResponse($quote, $policyDocumentsResult): void
    {
        $updateData = [];

        // Update premium details if available
        if (isset($policyDocumentsResult->policy_premium_without_tax)) {
            $updateData['price_vat_applicable'] = $policyDocumentsResult->policy_premium_without_tax;
        }

        if (isset($policyDocumentsResult->policy_premium_tax)) {
            $updateData['vat'] = $policyDocumentsResult->policy_premium_tax;
        }

        if (isset($policyDocumentsResult->policy_premium_with_tax)) {
            $updateData['price_with_vat'] = $policyDocumentsResult->policy_premium_with_tax;
        }

        // Store document URLs/references for later retrieval
        // These will be used to download and attach documents to IMCRM
        if (isset($policyDocumentsResult->policy_certificate_url)) {
            $updateData['insurer_policy_doc_id'] = $policyDocumentsResult->policy_certificate_url;
        }

        if (isset($policyDocumentsResult->premium_inv_doc_url)) {
            $updateData['insurer_tax_invoice_doc_id'] = $policyDocumentsResult->premium_inv_doc_url;
        } else { // TODO:: NGI:: test block will remove once response from provider is fixed against issue policy with payment reference number
            $updateData['insurer_tax_invoice_doc_id'] = $policyDocumentsResult?->policy_certificate_url;
        }

        if (isset($policyDocumentsResult->commision_inv_doc_url)) {
            $updateData['insurer_debit_note_doc_id'] = $policyDocumentsResult->commision_inv_doc_url;
        } else { // TODO:: NGI:: test block will remove once response from provider is fixed against issue policy with payment reference number
            $updateData['insurer_debit_note_doc_id'] = $policyDocumentsResult?->policy_certificate_url;
        }

        if (! empty($updateData)) {
            $quote->update($updateData);
        }
    }

    /**
     * Update payment from GetPolicyDocuments API response
     *
     * @param string $quoteCode
     * @param object $policyDocumentsResult
     * @return void
     */
    public function updatePaymentFromPolicyDocumentsResponse(string $quoteCode, $policyDocumentsResult): void
    {
        $updateData = $this->buildPaymentUpdateData($policyDocumentsResult);

        if (empty($updateData)) {
            return;
        }

        Payment::where('code', $quoteCode)->update($updateData);

        // Build payment splits data by removing fields not applicable to splits
        $paymentSplitsData = $this->buildPaymentSplitsData($updateData);
        PaymentSplits::where('code', $quoteCode)->update($paymentSplitsData);
    }

    /**
     * Build payment update data from policy documents response
     *
     * @param object $policyDocumentsResult
     * @return array
     */
    private function buildPaymentUpdateData(object $policyDocumentsResult): array
    {

        // ignore TODO:: NGI:: remaining
        // 'transaction_payment_status' => $validatedData['transaction_payment_status'],
        // 'broker_invoice_number' => $validatedData['broker_invoice_number'],

        // TODO:: NGI:: remaining
        // 'commmission_percentage' => $validatedData['commission_percentage'],
        // 'invoice_description' => $validatedData['invoice_description'],

        $updateData = [];

        // Commission details - direct mapping
        $commissionMapping = [
            'policy_commision_without_tax' => 'commission_vat_applicable',
            'policy_commision_with_tax' => 'commission',
            'policy_commision_tax' => 'commission_vat',
        ];

        foreach ($commissionMapping as $sourceField => $targetField) {
            if (isset($policyDocumentsResult->$sourceField)) {
                $updateData[$targetField] = $policyDocumentsResult->$sourceField;
        }
        }

        // Invoice date
        if (isset($policyDocumentsResult->premium_inv_dt)) {
            $updateData['insurer_invoice_date'] = Carbon::parse($policyDocumentsResult->premium_inv_dt)->format('Y-m-d');
        }

        // Invoice numbers with fallback for testing
        // TODO:: NGI:: premium_inv_no & commision_inv_no are required for book policy while missed from provider in case of missing payment_refrence in issue policy API call
        $updateData['insurer_tax_number'] = $policyDocumentsResult->premium_inv_no
            ?? 'P/INV/NN100TS10344' . rand(9999, 99999999) . rand(9999, 99999999);

        $updateData['insurer_commmission_invoice_number'] = $policyDocumentsResult->commision_inv_no
            ?? 'INV/NN100TS10344' . rand(9999, 99999999) . rand(9999, 99999999);

        return $updateData;
        }

    /**
     * Build payment splits data by removing fields not applicable to splits
     *
     * @param array $paymentData
     * @return array
     */
    private function buildPaymentSplitsData(array $paymentData): array
    {
        // Fields to exclude from payment splits update
        $excludeFields = [
            'commission',
            'insurer_invoice_date',
            'insurer_tax_number',
            'insurer_commmission_invoice_number',
        ];

        return array_diff_key($paymentData, array_flip($excludeFields));
    }
}
