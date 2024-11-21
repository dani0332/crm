<?php

namespace App\Observers;

use App\Enums\PaymentFrequency;
use App\Enums\PaymentStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Models\Payment;
use App\Models\PersonalQuote;
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
        // If payment status is changed to PAID, update the payment split to update the updated_at field of the payment split which is called the payment split observer
        if ($payment->frequency == PaymentFrequency::UPFRONT && $payment->isDirty('payment_status_id') && $payment->payment_status_id == PaymentStatusEnum::PAID) {
            info('Payment:Observer - Payment status changed to '.PaymentStatusEnum::PAID.' for payment code: '.$payment->code);
            $payment->paymentSplits()->first()->touch();
        }
    }

    /**
     * Update the price VAT fields of the payment.
     */
    private function updatePriceVat(Payment $payment): void
    {
        $modelType = null;
        $quoteId = null;
        if (! $payment->send_update_log_id) {
            $quote = $payment->paymentable;
            $quoteId = $quote->id;
            if ($payment->paymentable_type == PersonalQuote::class) {
                $modelType = QuoteTypes::getName($quote->quote_type_id)->value;
            } else {
                $modelType = quoteTypeCode::getName($payment->paymentable_type);
            }
        }

        [$priceWithoutVat, $vat] = app(SplitPaymentService::class)->calculateMasterPriceAndVat(
            $payment->frequency,
            $payment->total_price,
            $modelType,
            $quoteId,
            $payment->send_update_log_id
        );

        info('Payment:Observer VAT updated for '.$payment->code.' - priceWithoutVat: '.$priceWithoutVat.' - vat: '.$vat);

        Payment::withoutEvents(function () use ($payment, $priceWithoutVat, $vat) {
            $payment->update([
                'price_vat_applicable' => $priceWithoutVat,
                'price_vat' => $vat,
            ]);
        });
    }
}
