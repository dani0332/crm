<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Health\Adnic;

use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\Payment;
use Carbon\Carbon;

class AdnicQuoteUpdaterService
{
    public function updateQuoteFromIssuePolicyResponse($quote, $issuePolicyResult): void
    {
        $data = [
            'policy_number' => $issuePolicyResult?->PolicyInfo?->PolicyNo,
            'policy_start_date' => Carbon::parse($issuePolicyResult?->PolicyInfo?->PolicyStartDate)->format(config('constants.DB_DATE_FORMAT_MATCH')),
            'policy_expiry_date' => Carbon::parse($issuePolicyResult?->PolicyInfo?->PolicyEndDate)->format(config('constants.DB_DATE_FORMAT_MATCH')),
            'quote_status_id' => QuoteStatusEnum::PolicyIssued,
            'policy_issuance_status_id' => PolicyIssuanceStatusEnum::PolicyIssued,
            'quote_status_date' => now(),
            'price_vat_applicable' => $issuePolicyResult?->QuoteInfo?->PartnerPremium,
        ];

        // Filter out null values to avoid overwriting existing data
        $data = array_filter($data, fn ($value) => $value !== null);

        $quote->update($data);
    }

    public function updatePaymentFromIssuePolicyResponse(string $quoteCode, $issuePolicyResult): void
    {
        $data = [
             'insurer_tax_number' => $issuePolicyResult?->PolicyInfo?->DebitNoteNo ?? null,
             'insurer_commmission_invoice_number' => $issuePolicyResult?->PolicyInfo?->creditNoteNo ?? null,
         ];
         $data = array_filter($data, fn ($value) => $value !== null);

         Payment::where('code', $quoteCode)->update($data);
    }
}
