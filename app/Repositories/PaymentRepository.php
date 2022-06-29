<?php

use App\Interfaces\PaymentRepositoryInterface;

class PaymentRepository implements  PaymentRepositoryInterface
{
    public function getAllPayments($leadId)
    {
        return Payment::where('lead_id', $leadId)->get();
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
