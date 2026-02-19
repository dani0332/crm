<?php

namespace App\Services\PolicyIssuanceAutomation\Cyber;

use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class AwnicQuoteUpdaterService
{
    public function updateQuoteFromIssuePolicyResponse($quote, $issuePolicyResult): void
    {
        DB::transaction(function () use ($quote, $issuePolicyResult) {
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
            ]);

            // Use Eloquent fetch/update for auditing (do NOT bypass models/events)
            $cyberQuote = $quote->cyberQuote;
            if ($cyberQuote) {
                $cyberQuote->insurer_debit_note_doc_id = $issuePolicyResult?->policyInfo?->drcrDocId;
                $cyberQuote->insurer_tax_invoice_doc_id = $issuePolicyResult?->policyInfo?->taxInvoiceDocId;
                $cyberQuote->insurer_policy_doc_id = $issuePolicyResult?->policyInfo?->policyDocId;
                $cyberQuote->save();
            }

            $quote->cyberPlanDetail()->update([
                'insurerQuoteNo' => $issuePolicyResult?->QuoteRefNo,
            ]);
        });
    }

    public function updatePaymentFromIssuePolicyResponse(string $quoteCode, $issuePolicyResult): void
    {
        $policyInfo = $issuePolicyResult?->policyInfo;

        // Calculate commission percentage
        // commission_percentage = (commissionAmt / premiumAmount) * 100
        $commissionAmt = isset($policyInfo?->commissionAmt) ? (float) $policyInfo->commissionAmt : 0.0;
        $premiumAmount = isset($policyInfo?->premiumAmount) ? (float) $policyInfo->premiumAmount : 0.0;

        $commissionPercentage = 0.0;
        if ($premiumAmount > 0) {
            $commissionPercentage = ($commissionAmt / $premiumAmount) * 100;
        }

        Payment::where('code', $quoteCode)->update([
            'commission_vat_applicable' => $issuePolicyResult?->policyInfo?->commissionAmt,
            'commission' => $issuePolicyResult?->policyInfo?->commissionPayableAmt,
            'commission_vat' => $issuePolicyResult?->policyInfo?->commissionVatAmt,
            'commmission_percentage' => $commissionPercentage,
            'insurer_tax_number' => $issuePolicyResult?->policyInfo?->invoiceNo ?? null,
            'insurer_invoice_date' => $issuePolicyResult?->policyInfo?->policyIssuedDate ?? null,
            'insurer_commmission_invoice_number' => $issuePolicyResult?->policyInfo?->creditNoteNo ?? null,
        ]);
    }
}
