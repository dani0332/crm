<?php

namespace App\Observers;

use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Models\PaymentSplits;
use App\Models\PersonalQuote;
use App\Services\SplitPaymentService;

class PaymentSplitsObserver
{
    /**
     * Handle the PaymentSplits "created" event.
     */
    public function created(PaymentSplits $paymentSplits): void
    {
        $this->updateSplitPriceVat($paymentSplits);
    }

    /**
     * Handle the PaymentSplits "updated" event.
     */
    public function updated(PaymentSplits $paymentSplits): void
    {
        if ($paymentSplits->isDirty('payment_amount')) {
            $this->updateSplitPriceVat($paymentSplits);
        }
    }

    /**
     * Update the price VAT fields of the payment split.
     */
    private function updateSplitPriceVat(PaymentSplits $paymentSplits): void
    {
        $masterPayment = $paymentSplits->payment;
        $totalSplitPayments = $masterPayment->total_payments;
        $quote = $masterPayment->paymentable;
        $modelType = null;
        $quoteId = null;
        if (! $masterPayment->send_update_log_id) {
            $quote = $masterPayment->paymentable;
            $quoteId = $quote->id;
            if ($masterPayment->paymentable_type == PersonalQuote::class) {
                $modelType = QuoteTypes::getName($quote->quote_type_id)->value;
            } else {
                $modelType = quoteTypeCode::getName($masterPayment->paymentable_type);
            }
        }

        [$priceWithoutVat, $vat] = app(SplitPaymentService::class)->calculatePriceAndVat(
            $masterPayment->frequency,
            $masterPayment->total_price,
            $paymentSplits->sr_no,
            $paymentSplits->payment_amount,
            $modelType,
            $quoteId,
            $totalSplitPayments,
            $masterPayment->send_update_log_id
        );

        PaymentSplits::withoutEvents(function () use ($paymentSplits, $priceWithoutVat, $vat) {
            $paymentSplits->update([
                'price_vat_applicable' => $priceWithoutVat,
                'price_vat' => $vat,
            ]);
        });
    }
}
