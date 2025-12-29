<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Health\Adnic;

use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\Payment;

class AdnicQuoteUpdaterService
{
    public function updateQuoteFromIssuePolicyResponse($quote, $issuePolicyResult): void
    {
        $data = [
            'policy_number' => $issuePolicyResult?->PolicyInfo?->PolicyNo,
            'policy_start_date' => $issuePolicyResult?->PolicyInfo?->PolicyStartDate,
            'policy_expiry_date' => $issuePolicyResult?->PolicyInfo?->PolicyEndDate,
            'quote_status_id' => QuoteStatusEnum::PolicyIssued,
            'policy_issuance_status_id' => PolicyIssuanceStatusEnum::PolicyIssued,
            'quote_status_date' => now(),
            
            /* 'policy_issuance_date' => $issuePolicyResult?->PolicyInfo?->policyIssuedDate,
            'price_vat_applicable' => $issuePolicyResult?->PolicyInfo?->premiumAmount,
            'vat' => $issuePolicyResult?->PolicyInfo?->prmVatAmt,
            'price_with_vat' => $issuePolicyResult?->PolicyInfo?->FinalPremium,
            'insurer_quote_number' => $issuePolicyResult?->QuoteRefNo ?? null,
            'insurer_debit_note_doc_id' => $issuePolicyResult?->PolicyDocumentInfo?->CommisionNoteDocumentId,
            'insurer_tax_invoice_doc_id' => $issuePolicyResult?->PolicyDocumentInfo?->TaxInvoiceDocumentId,
            'insurer_policy_doc_id' => $issuePolicyResult?->PolicyDocumentInfo?->PolicyDocumentId, */
        ];

        // Filter out null values to avoid overwriting existing data
        $data = array_filter($data, fn ($value) => $value !== null);

        $quote->update($data);
    }

    public function updatePaymentFromIssuePolicyResponse(string $quoteCode, $issuePolicyResult): void
    {
       /*  
       $data = [
            'commission_vat_applicable' => $issuePolicyResult?->PolicyInfo?->commissionPayableAmt,
            'commission' => $issuePolicyResult?->PolicyInfo?->CommissionAmount,
            'commission_vat' => $issuePolicyResult?->PolicyInfo?->commissionVatAmt,
            'commmission_percentage' => $issuePolicyResult?->PolicyInfo?->CommissionPercentage ?? 0,
            'insurer_tax_number' => $issuePolicyResult?->PolicyInfo?->invoiceNo ?? null,
            'insurer_invoice_date' => $issuePolicyResult?->PolicyInfo?->policyIssuedDate ?? null,
            'insurer_commmission_invoice_number' => $issuePolicyResult?->PolicyInfo?->creditNoteNo ?? null,
        ];  
         // Filter out null values to avoid overwriting existing data
        $data = array_filter($data, fn ($value) => $value !== null);

        Payment::where('code', $quoteCode)->update($data); 
        
        */
    }
}
