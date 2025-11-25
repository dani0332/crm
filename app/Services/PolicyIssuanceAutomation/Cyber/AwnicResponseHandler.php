<?php

namespace App\Services\PolicyIssuanceAutomation\Cyber;

use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\Payment;

class AwnicResponseHandler
{
    /**
     * Update quote with issue policy response data
     *
     * @param mixed $quote
     * @param \stdClass $issuePolicyResult
     * @return void
     */
    public function updateQuoteFromIssuePolicyResponse($quote, $issuePolicyResult): void
    {
        $quote->update([
            'policy_number' => $issuePolicyResult?->policyInfo?->policyNo,
            'policy_issuance_date' => $issuePolicyResult?->policyInfo?->policyIssuedDate,
            'policy_start_date' => $issuePolicyResult?->policyInfo?->policyStartDate,
            'policy_expiry_date' => $issuePolicyResult?->policyInfo?->policyEndDate,
            'price_vat_applicable' => $issuePolicyResult?->policyInfo?->premiumAmount,
            'vat' => $issuePolicyResult?->policyInfo?->prmVatAmt,
            'price_with_vat' => $issuePolicyResult?->policyInfo?->prmPayableAmt,
            'insurer_quote_number' => $issuePolicyResult?->QuoteRefNo ?? null,
            'quote_status_id' => QuoteStatusEnum::PolicyIssued,
            'policy_issuance_status_id' => PolicyIssuanceStatusEnum::PolicyIssued,
            'quote_status_date' => now(),
            'awni_drcr_doc_id' => $issuePolicyResult?->policyInfo?->drcrDocId,
            'awni_tax_invoice_doc_id' => $issuePolicyResult?->policyInfo?->taxInvoiceDocId,
            'awni_policy_doc_id' => $issuePolicyResult?->policyInfo?->policyDocId,
        ]);
    }

    /**
     * Update cyber quote with document IDs from issue policy response
     *
     * @param mixed $cyberQuote
     * @param \stdClass $issuePolicyResult
     * @return void
     */
    public function updateCyberQuoteFromIssuePolicyResponse($cyberQuote, $issuePolicyResult): void
    {
    }

    /**
     * Update payment with commission data from issue policy response
     *
     * @param string $quoteCode
     * @param \stdClass $issuePolicyResult
     * @return void
     */
    public function updatePaymentFromIssuePolicyResponse(string $quoteCode, $issuePolicyResult): void
    {
        Payment::where('code', $quoteCode)->update([
            'commission_vat_applicable' => $issuePolicyResult?->policyInfo?->commissionPayableAmt,
            'commission' => $issuePolicyResult?->policyInfo?->commissionAmt,
            'commission_vat' => $issuePolicyResult?->policyInfo?->commissionVatAmt,
            'commmission_percentage' => $issuePolicyResult?->policyInfo?->CommissionPercentage ?? 0, // TODO: need to verify commission percentage is not coming in response
            'insurer_tax_number' => $issuePolicyResult?->policyInfo?->invoiceNo ?? null,
            'insurer_invoice_date' => $issuePolicyResult?->policyInfo?->policyIssuedDate ?? null,
            //TODO : Remove time stamp and use creditNoteNo if available
            'insurer_commmission_invoice_number' => $issuePolicyResult?->policyInfo?->creditNoteNo ?? null,
        ]);
    }

    /**
     * Build step response array
     *
     * @param string $step
     * @param bool $status
     * @param string|null $message
     * @param mixed $error
     * @param mixed $data
     * @return array
     */
    public function buildStepResponse(string $step, bool $status = false, ?string $message = null, $error = null, $data = null): array
    {
        return [
            'status' => $status,
            'completed_step' => $step,
            'message' => $message,
            'error' => $error,
            'data' => $data,
        ];
    }
}

