<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance;

use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\Payment;
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
            'price_vat_applicable' => $createPolicyResult?->policy_premium ?? $quote->price_vat_applicable,
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
            $updateData['price_vat_not_applicable'] = $policyDocumentsResult->policy_premium_without_tax;
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
        }

        if (isset($policyDocumentsResult->commision_inv_doc_url)) {
            $updateData['insurer_debit_note_doc_id'] = $policyDocumentsResult->commision_inv_doc_url;
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
        $updateData = [];

        // Commission details
        if (isset($policyDocumentsResult->policy_commision_with_tax)) {
            $updateData['commission_vat_applicable'] = $policyDocumentsResult->policy_commision_with_tax;
        }

        if (isset($policyDocumentsResult->policy_commision_without_tax)) {
            $updateData['commission'] = $policyDocumentsResult->policy_commision_without_tax;
        }

        if (isset($policyDocumentsResult->policy_commision_tax)) {
            $updateData['commission_vat'] = $policyDocumentsResult->policy_commision_tax;
        }

        // Invoice details
        if (isset($policyDocumentsResult->premium_inv_no)) {
            $updateData['insurer_tax_number'] = $policyDocumentsResult->premium_inv_no;
        }

        if (isset($policyDocumentsResult->premium_inv_dt)) {
            $updateData['insurer_invoice_date'] = Carbon::parse($policyDocumentsResult->premium_inv_dt)->format('Y-m-d');
        }

        if (isset($policyDocumentsResult->commision_inv_no)) {
            $updateData['insurer_commmission_invoice_number'] = $policyDocumentsResult->commision_inv_no;
        }

        if (! empty($updateData)) {
            Payment::where('code', $quoteCode)->update($updateData);
        }
    }
}
