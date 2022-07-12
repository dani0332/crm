<?php

namespace App\Interfaces;

interface PaymentRepositoryInterface
{
    public function getAllPayments($leadId, $quoteTypeId);
    public function getPaymentById($paymentId);
    public function deletePayment($paymentId);
    public function createOrder(array $paymentInformation);
    public function updateOrder($paymentId, array $newInformation);
}
