<?php

namespace App\Services\Life;

use App\Services\BaseService;
use App\Models\Payment;
use App\Services\SplitPaymentService;

class PaymentService extends BaseService
{
    public function updatePriceVatApplicableAndVat($quote, $quoteType)
    {
        /* Start - Temporarily adding for correcting historic data  */
        info('Start - Temporarily adding for correcting historic data'.$quote->uuid);
        /* calculate price and vat for payments for old payment data  where price_vat_applicable is not available */
        $quotePayment = Payment::where('code', $quote->code)->mainLeadPayment()->with('paymentSplits')->first();
        if ($quotePayment) {
            if (! $quotePayment->price_vat_applicable) {
                [$priceWithoutVat, $vat] = app(SplitPaymentService::class)->calculateMasterPriceAndVat($quotePayment->frequency, $quotePayment->total_price, $quoteType, $quote->id);
                $quotePayment->update([
                    'price_vat_applicable' => $priceWithoutVat,
                    'price_vat' => $vat,
                ]);
            }

            /* calculate price and vat for split payments for old split payment data where price_vat_applicable is not available */
            $paymentSplits = $quotePayment->paymentSplits;
            if ($paymentSplits->whereNull('price_vat_applicable')->count()) {
                foreach ($quotePayment->paymentSplits as $splitPayment) {
                    if (isset($splitPayment->payment_method) && $splitPayment->payment_method != null) {
                        $splitAmount = $splitPayment->payment_amount;
                        if ($splitPayment->sr_no === 1) {
                            $splitAmount = $splitPayment->payment_amount + $quotePayment->discount_value;
                        }
                        [$priceWithoutVat, $vat] = app(SplitPaymentService::class)->calculatePriceAndVat($quotePayment->frequency, $quotePayment->total_price, $splitPayment->sr_no, $splitAmount, $quoteType, $quote->id, count($paymentSplits));
                        $splitPayment->update([
                            'price_vat_applicable' => $priceWithoutVat,
                            'price_vat' => $vat,
                        ]);
                    }
                }
            }
        }
        info('End - Temporarily adding for correcting historic data '.$quote->uuid);
        /* End - Temporarily adding for correcting historic data  */
    }
}
