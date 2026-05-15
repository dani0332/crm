<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance;

use App\Enums\NgiEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\Payment;
use App\Models\PaymentSplits;
use Carbon\Carbon;

class NgiQuoteUpdaterService
{
    public function updateQuoteInsurerAndIssuanceStatus($quote, $step, $isSuccess): void
    {

        // Issuance status must be set to success or failed based on the API response
        $dataToUpdate = [
            'api_issuance_status_id' => $isSuccess ? PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID : PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID,
        ];

        // If the API response is not success, then the insurer API status must be set to failed with related step (Policy Document Retrieval)
        if (! $isSuccess) {
            $insurerApiStatusId = match ($step) {
                NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE => PolicyIssuanceEnum::PIA_POLICY_ISSUANCE_API_FAILED_STATUS_ID,
                NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM => PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID,
                NgiEnum::STEP_BOOK_POLICY => PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID,
                default => null,
            };
            $dataToUpdate['insurer_api_status_id'] = $insurerApiStatusId;
        } elseif ($isSuccess && $step === NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM) { // this step can call multiple time for each retry and it may success after fails in next retry so if success then set insurer api status to null
            $dataToUpdate['insurer_api_status_id'] = null;
        }

        // update quote with the data to update
        $quote->update($dataToUpdate);

    }

    /**
     * Update quote from CreatePolicyFromQuote API response
     *
     * @param  mixed  $quote
     * @param  object  $createPolicyResult
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
     * @param  mixed  $quote
     * @param  object  $policyDocumentsResult
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

        if (! empty($updateData)) {
            $quote->update($updateData);
        }
    }

    /**
     * Update payment from GetPolicyDocuments API response
     *
     * @param  object  $policyDocumentsResult
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

        // Invoice numbers - direct mapping
        if (isset($policyDocumentsResult?->premium_inv_no)) {
            $updateData['insurer_tax_number'] = $policyDocumentsResult?->premium_inv_no ?? '';
        }

        if (isset($policyDocumentsResult?->commision_inv_no)) {
            $updateData['insurer_commmission_invoice_number'] = $policyDocumentsResult?->commision_inv_no ?? '';
        }

        $updateData['commmission_percentage'] = ($policyDocumentsResult->policy_commision_without_tax / $policyDocumentsResult->policy_premium_without_tax) * 100;

        return $updateData;
    }

    /**
     * Build payment splits data by removing fields not applicable to splits
     */
    private function buildPaymentSplitsData(array $paymentData): array
    {
        // Fields to exclude from payment splits update
        $excludeFields = [
            'commission',
            'insurer_invoice_date',
            'insurer_tax_number',
            'insurer_commmission_invoice_number',
            'commmission_percentage',
        ];

        return array_diff_key($paymentData, array_flip($excludeFields));
    }
}
