<?php

namespace App\Services;

class PaymentLinkService extends BaseService
{
    protected $travelService;

    public function __construct(
        TravelQuoteService $travelService,
    ) {
        $this->travelService = $travelService;
    }

    public function getPaymentLink($payment, $quoteTypeId, $leadId)
    {
        $payment->quote_id = $leadId;
        $payment->quote_type_id = $quoteTypeId;
        $payment->save();

        return $payment->payment_link;
    }
}
