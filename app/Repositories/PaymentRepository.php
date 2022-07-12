<?php

use App\Interfaces\PaymentRepositoryInterface;
use App\Models\Payment;

class PaymentRepository implements  PaymentRepositoryInterface
{
    public function getAllPayments($leadId, $quoteTypeId)
    {
        return Payment::where('quote_id', $leadId)->where('quote_type_id', $quoteTypeId)->get();
    }

    public function getPaymentById($paymentId)
    {
        return Payment::find($paymentId);
    }

    public function deletePayment($paymentId)
    {
        return Payment::destroy($paymentId);
    }

    public function createOrder(array $paymentInformation)
    {
        return Payment::create($paymentInformation);
    }

    public function updateOrder($paymentId, array $newInformation)
    {
        return Payment::find($paymentId)->update($newInformation);
    }
}
