<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Travel\Dic;

use App\Models\PolicyIssuance;
use App\Models\TravelQuote;

class DicRequestBuilder
{
    /**
     * EnsuredIT embed API: POST .../embed/v1/products/buy/client
     * Body: `{ "policy_id": "<uuid>" }`.
     *
     * @return array{policy_id: string}|array{}
     */
    public function buildIssuePolicyPayload($quote): array
    {
        $payment = $quote->payments()->mainLeadPayment()->first();
        $transactionId = $payment?->paymentSplits?->first()?->paymentCharges?->transaction_id;

        return [
            'policy_id' => $quote->insurer_quote_number,
            'payment_details' => [
                'Transaction_id' => $transactionId,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildGetPolicyDocPayload(TravelQuote $quote, PolicyIssuance $policyIssuance): array
    {
        return [
            'quoteCode' => $quote->code,
            'policyIssuanceId' => $policyIssuance->id,
            'policyNo' => $quote->policy_number,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildGetBrokerInvoicePayload(TravelQuote $quote, PolicyIssuance $policyIssuance): array
    {
        return [
            'quoteCode' => $quote->code,
            'policyIssuanceId' => $policyIssuance->id,
            'policyNo' => $quote->policy_number,
        ];
    }
}
