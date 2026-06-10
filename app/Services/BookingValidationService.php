<?php

namespace App\Services;

use App\Enums\PaymentMethodsEnum;
use App\Enums\QuoteTypeId;
use App\Exceptions\BookingValidationException;
use App\Models\Payment;

class BookingValidationService
{
    /**
     * Run all pre-booking validations that must pass before any Sage operation is triggered.
     * Called from postBookPolicyToSage() so both manual and automation flows are covered.
     *
     * @throws BookingValidationException
     */
    public function validate(Payment $payment, int $quoteTypeId): void
    {
        $errors = [
            ...$this->validatePolicyAmount($payment),
            ...$this->validateRequiredPaymentFields($payment),
            ...$this->validateCommissionFields($payment),
            ...($quoteTypeId === QuoteTypeId::Car ? $this->validateCarCommissionFields($payment) : []),
        ];

        if (! empty($errors)) {
            throw new BookingValidationException($errors);
        }
    }

    private function validatePolicyAmount(Payment $payment): array
    {
        // Zero-price policies are only allowed for Credit Approval payment method.
        // Any other payment method with a zero total_price is an invalid booking (e.g. OCR misread).
        if (($payment->total_price ?? 0) == 0 && $payment->payment_methods_code !== PaymentMethodsEnum::CreditApproval) {
            return ['Policy amount must be greater than 0.'];
        }

        return [];
    }

    private function validateRequiredPaymentFields(Payment $payment): array
    {
        $errors = [];

        if (empty($payment->insurer_invoice_date)) {
            $errors[] = 'Insurer Invoice date is required.';
        }
        if (empty($payment->insurer_tax_number)) {
            $errors[] = 'Insurer tax invoice number is required.';
        }
        if (empty($payment->insurer_commmission_invoice_number)) {
            $errors[] = 'Insurer Commission Invoice Number is required.';
        }

        return $errors;
    }

    private function validateCommissionFields(Payment $payment): array
    {
        if (empty($payment->commission_vat_not_applicable) && empty($payment->commission_vat_applicable)) {
            return ['Commission (VAT NOT APPLICABLE) OR Commission (VAT APPLICABLE) is required.'];
        }

        return [];
    }

    /**
     * CAR-specific commission fields required for Sage AR/AP invoice creation.
     */
    private function validateCarCommissionFields(Payment $payment): array
    {
        $errors = [];

        if (empty($payment->commmission_percentage)) {
            $errors[] = 'Commission percentage is required.';
        }
        if (empty($payment->commission_vat)) {
            $errors[] = 'Commission VAT is required.';
        }
        if (empty($payment->commission)) {
            $errors[] = 'Total commission is required.';
        }

        return $errors;
    }
}
