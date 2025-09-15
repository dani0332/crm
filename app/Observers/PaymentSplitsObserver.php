<?php

namespace App\Observers;

use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Models\PaymentSplits;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
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
        // As the vat calculation changes according to frequency so cannot apply isDirty('payment_amount')
        $this->updateSplitPriceVat($paymentSplits);
    }

    /**
     * Update the price VAT fields of the payment split.
     */
    private function updateSplitPriceVat(PaymentSplits $paymentSplits): void
    {
        LoggerService::info('PaymentSplits:Observer - Starting VAT calculation for payment code: '.$paymentSplits->code, extra: [
            'sr_no' => $paymentSplits->sr_no,
        ]);

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
                LoggerService::info('PaymentSplits:Observer - Found personal quote for payment code: '.$paymentSplits->code, extra: [
                    'sr_no' => $paymentSplits->sr_no,
                ]);
            } else {
                $modelType = quoteTypeCode::getName($masterPayment->paymentable_type);
                LoggerService::info('PaymentSplits:Observer - Found non perosnal for payment code: '.$paymentSplits->code, extra: [
                    'sr_no' => $paymentSplits->sr_no,
                ]);
            }
        } else {
            LoggerService::info('PaymentSplits:Observer - Processing send update log for payment code: '.$paymentSplits->code, extra: [
                'sr_no' => $paymentSplits->sr_no,
                'send_update_log_id' => $masterPayment->send_update_log_id,
            ]);
        }

        $splitAmount = $paymentSplits->payment_amount;
        if ($paymentSplits->sr_no === 1) {
            $splitAmount = $paymentSplits->payment_amount + $masterPayment->discount_value;
            LoggerService::info('PaymentSplits:Observer - Applied discount to first split for payment code: '.$paymentSplits->code, extra: [
                'sr_no' => $paymentSplits->sr_no,
                'original_amount' => $paymentSplits->payment_amount,
                'discount_value' => $masterPayment->discount_value,
                'final_split_amount' => $splitAmount,
            ]);
        }

        LoggerService::info('PaymentSplits:Observer - Calculating price and VAT for payment code: '.$paymentSplits->code, extra: [
            'sr_no' => $paymentSplits->sr_no,
            'total_payments' => $totalSplitPayments,
            'frequency' => $masterPayment->frequency,
            'total_price' => $masterPayment->total_price,
            'split_amount' => $splitAmount,
        ]);

        [$priceWithoutVat, $vat] = app(SplitPaymentService::class)->calculatePriceAndVat(
            $masterPayment->frequency,
            $masterPayment->total_price,
            $paymentSplits->sr_no,
            $splitAmount,
            $modelType,
            $quoteId,
            $totalSplitPayments,
            $masterPayment->code,
            $masterPayment->send_update_log_id
        );

        LoggerService::info('PaymentSplits:Observer - VAT calculation completed for payment code: '.$paymentSplits->code, extra: [
            'sr_no' => $paymentSplits->sr_no,
            'price_without_vat' => $priceWithoutVat,
            'vat' => $vat,
        ]);

        PaymentSplits::withoutEvents(function () use ($paymentSplits, $priceWithoutVat, $vat) {
            $paymentSplits->update([
                'price_vat_applicable' => $priceWithoutVat,
                'price_vat' => $vat,
            ]);

            LoggerService::info('PaymentSplits:Observer - Payment split updated for payment code: '.$paymentSplits->code, extra: [
                'sr_no' => $paymentSplits->sr_no,
            ]);
        });
    }
}
