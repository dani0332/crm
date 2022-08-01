<?php

namespace App\Repositories;

use App\Interfaces\PaymentRepositoryInterface;
use App\Models\Payment;
use App\Services\PaymentLinkService;

class PaymentRepository implements PaymentRepositoryInterface
{
    protected $paymentService;

    public function __construct(PaymentLinkService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function getPaymentsByQuoteId($quoteId, $quoteTypeId)
    {
        return Payment::where('quote_id', $quoteId)->where('quote_type_id', $quoteTypeId)->get();
    }

    public function getPaymentById($paymentId)
    {
        return Payment::find($paymentId);
    }

    public function deletePayment($paymentId)
    {
        return Payment::destroy($paymentId);
    }

    public function createPayment(array $paymentInformation)
    {
        return Payment::create($paymentInformation);
    }

    public function updatePayment($paymentId, array $newInformation)
    {
        return Payment::find($paymentId)->update($newInformation);
    }

    public function getPaymentLink($paymentId, $quoteTypeId, $leadId)
    {
        $payment = $this->getPaymentById($paymentId);
        $paymentLink = $this->paymentService->getPaymentLink($payment, $quoteTypeId, $leadId);

        return $paymentLink;
    }
}
