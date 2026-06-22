<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Travel\Dic;

class DicRequestBuilder
{
    public function buildIssuePolicyPayload($quote): array
    {
        $payment = $quote->payments()->mainLeadPayment()->first();
        $transactionId = $payment?->paymentSplits?->first()?->paymentCharges?->transaction_id;
        $authCode = $payment?->paymentSplits?->first()?->cc_payment_id;

        return [
            'policy_id' => $quote->insurer_quote_number,
            'payment_details' => [
                'transaction_id' => $transactionId,
                'authCode' => $authCode,
            ],
        ];
    }
}
