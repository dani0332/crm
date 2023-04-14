<?php

namespace App\Interfaces;

use App\Services\PaymentLinkService;

interface PaymentRepositoryInterface
{
    public function getPaymentsByQuoteId($quoteId, $quoteTypeId);
    public function getPaymentById($paymentId);
    public function deletePayment($paymentId);
    public function createPayment(array $paymentInformation);
    public function updatePayment($paymentId, array $newInformation);
    public function getPaymentLink(PaymentLinkService $paymentLinkService, $paymentId, $quoteTypeId, $leadId);
}
