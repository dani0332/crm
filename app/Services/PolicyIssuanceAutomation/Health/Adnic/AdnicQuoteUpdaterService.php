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
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH') ?: 'Y-m-d';

        $policyStartRaw = data_get($issuePolicyResult, 'PolicyInfo.PolicyStartDate');
        $policyEndRaw = data_get($issuePolicyResult, 'PolicyInfo.PolicyEndDate');

        $data = [
            'policy_number' => data_get($issuePolicyResult, 'PolicyInfo.PolicyNo'),
            'policy_start_date' => $policyStartRaw !== null && $policyStartRaw !== ''
                ? Carbon::parse($policyStartRaw)->format($dateFormat)
                : null,
            'policy_expiry_date' => $policyEndRaw !== null && $policyEndRaw !== ''
                ? Carbon::parse($policyEndRaw)->format($dateFormat)
                : null,
            'quote_status_id' => QuoteStatusEnum::PolicyIssued,
            'policy_issuance_status_id' => PolicyIssuanceStatusEnum::PolicyIssued,
            'quote_status_date' => now(),
            'price_vat_applicable' => data_get($issuePolicyResult, 'QuoteInfo.PartnerPremium'),
        ];

        // Filter out null values to avoid overwriting existing data
        $data = array_filter($data, fn ($value) => $value !== null);

        $quote->update($data);
    }

    public function updatePaymentFromIssuePolicyResponse(string $quoteCode, $issuePolicyResult): void
    {
        $data = [
            'insurer_tax_number' => data_get($issuePolicyResult, 'PolicyInfo.DebitNoteNo'),
            'insurer_commmission_invoice_number' => data_get($issuePolicyResult, 'PolicyInfo.creditNoteNo'),
        ];
        $data = array_filter($data, fn ($value) => $value !== null);

        Payment::where('code', $quoteCode)->update($data);
    }
}
