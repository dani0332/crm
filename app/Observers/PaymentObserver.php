<?php

namespace App\Observers;

use App\Enums\QuoteTypes;
use App\Models\Payment;
use App\Services\SplitPaymentService;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        $this->updatePriceVat($payment);
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        // Only update VAT if total_price has changed
        if ($payment->isDirty('total_price')) {
            $this->updatePriceVat($payment);
        }
    }

    /**
     * Update the price VAT fields of the payment.
     */
    private function updatePriceVat(Payment $payment): void
    {
        $quote = $payment->paymentable;
        $modelType = QuoteTypes::getName($quote->quote_type_id)->value;

        [$priceWithoutVat, $vat] = app(SplitPaymentService::class)->calculateMasterPriceAndVat(
            $payment->frequency,
            $payment->total_price,
            $modelType,
            $quote->id
        );

        Payment::withoutEvents(function () use ($payment, $priceWithoutVat, $vat) {
            $payment->update([
                'price_vat_applicable' => $priceWithoutVat,
                'price_vat' => $vat,
            ]);
        });
    }
}
