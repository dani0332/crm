<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\TravelQuote;
use App\Enums\quoteTypeCode;
use App\Enums\PaymentStatusEnum;
use App\Traits\GenericQueriesAllLobs;


class PaymentObserver
{
    use GenericQueriesAllLobs;
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        //echo $payment->payment_status_id;
        return;
        if (!array_key_exists('payment_status_id', $payment->getDirty())) {
            return;
        }
        
        $quoteType = '';
        if ($payment->paymentable_type == CarQuote::class) {
            $quoteType = quoteTypeCode::Car;
        } elseif ($payment->paymentable_type == HealthQuote::class) {
            $quoteType = quoteTypeCode::Health;
        } elseif ($payment->paymentable_type == TravelQuote::class) {
            $quoteType = quoteTypeCode::Travel;
        }
        // If a quote type is found, get the corresponding quote object
        if ($quoteType !== '') {
            $quoteModel = $this->getQuoteObject($quoteType, $payment->paymentable_id);

        
            if ($quoteModel) {
                //dd($quoteModel); 
                echo $payment->payment_status_id;   
                $quoteModel->payment_status_id = $payment->payment_status_id;
               
                $dirtyValues = $payment->getDirty();
                if ($dirtyValues['payment_status_id'] == PaymentStatusEnum::CAPTURED) {
                   // $quoteModel->paid_at_payments = '2024-05-08 14:04:18';
                } 
                $quoteModel->save();  dd($quoteModel->getErrors());
                echo $quoteModel->paid_at_payments; 
                dd($quoteModel);            
                
            }
        }
        dd("No quote type found");
        //dd($quoteModel);
        //
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "force deleted" event.
     */
    public function forceDeleted(Payment $payment): void
    {
        //
    }
}
