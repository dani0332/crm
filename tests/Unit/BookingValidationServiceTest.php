<?php

declare(strict_types=1);

use App\Enums\PaymentMethodsEnum;
use App\Enums\QuoteTypeId;
use App\Exceptions\BookingValidationException;
use App\Models\Payment;
use App\Services\BookingValidationService;

function makePayment(array $attrs = []): Payment
{
    $payment = new Payment;
    $defaults = [
        'total_price' => 1000.00,
        'payment_methods_code' => PaymentMethodsEnum::CreditCard,
        'insurer_invoice_date' => '2025-01-01',
        'insurer_tax_number' => 'TAX-001',
        'insurer_commmission_invoice_number' => 'COM-001',
        'commission_vat_not_applicable' => 50.00,
        'commission_vat_applicable' => null,
        'commmission_percentage' => 5.00,
        'commission_vat' => 2.50,
        'commission' => 52.50,
    ];

    foreach (array_merge($defaults, $attrs) as $key => $value) {
        $payment->$key = $value;
    }

    return $payment;
}

test('passes validation for a complete valid car payment', function () {
    $payment = makePayment();

    expect(fn () => app(BookingValidationService::class)->validate($payment, QuoteTypeId::Car))
        ->not->toThrow(BookingValidationException::class);
});

test('blocks booking when policy amount is zero and payment method is not credit approval', function () {
    $payment = makePayment(['total_price' => 0, 'payment_methods_code' => PaymentMethodsEnum::CreditCard]);

    expect(fn () => app(BookingValidationService::class)->validate($payment, QuoteTypeId::Car))
        ->toThrow(BookingValidationException::class, 'Policy amount must be greater than 0.');
});

test('blocks booking when policy amount is null and payment method is not credit approval', function () {
    $payment = makePayment(['total_price' => null, 'payment_methods_code' => PaymentMethodsEnum::Cheque]);

    expect(fn () => app(BookingValidationService::class)->validate($payment, QuoteTypeId::Car))
        ->toThrow(BookingValidationException::class, 'Policy amount must be greater than 0.');
});

test('allows zero amount for credit approval payment method', function () {
    $payment = makePayment(['total_price' => 0, 'payment_methods_code' => PaymentMethodsEnum::CreditApproval]);

    expect(fn () => app(BookingValidationService::class)->validate($payment, QuoteTypeId::Car))
        ->not->toThrow(BookingValidationException::class);
});

test('blocks booking when insurer invoice date is missing', function () {
    $payment = makePayment(['insurer_invoice_date' => null]);

    expect(fn () => app(BookingValidationService::class)->validate($payment, QuoteTypeId::Car))
        ->toThrow(BookingValidationException::class, 'Insurer Invoice date is required.');
});

test('blocks booking when insurer tax number is missing', function () {
    $payment = makePayment(['insurer_tax_number' => null]);

    expect(fn () => app(BookingValidationService::class)->validate($payment, QuoteTypeId::Car))
        ->toThrow(BookingValidationException::class, 'Insurer tax invoice number is required.');
});

test('blocks booking when insurer commission invoice number is missing', function () {
    $payment = makePayment(['insurer_commmission_invoice_number' => null]);

    expect(fn () => app(BookingValidationService::class)->validate($payment, QuoteTypeId::Car))
        ->toThrow(BookingValidationException::class, 'Insurer Commission Invoice Number is required.');
});

test('blocks booking when both commission vat fields are empty', function () {
    $payment = makePayment(['commission_vat_not_applicable' => null, 'commission_vat_applicable' => null]);

    expect(fn () => app(BookingValidationService::class)->validate($payment, QuoteTypeId::Car))
        ->toThrow(BookingValidationException::class, 'Commission (VAT NOT APPLICABLE) OR Commission (VAT APPLICABLE) is required.');
});

test('passes when only commission_vat_applicable is set', function () {
    $payment = makePayment(['commission_vat_not_applicable' => null, 'commission_vat_applicable' => 50.00]);

    expect(fn () => app(BookingValidationService::class)->validate($payment, QuoteTypeId::Car))
        ->not->toThrow(BookingValidationException::class);
});

test('blocks car booking when commission percentage is missing', function () {
    $payment = makePayment(['commmission_percentage' => null]);

    expect(fn () => app(BookingValidationService::class)->validate($payment, QuoteTypeId::Car))
        ->toThrow(BookingValidationException::class, 'Commission percentage is required.');
});

test('blocks car booking when commission vat is missing', function () {
    $payment = makePayment(['commission_vat' => null]);

    expect(fn () => app(BookingValidationService::class)->validate($payment, QuoteTypeId::Car))
        ->toThrow(BookingValidationException::class, 'Commission VAT is required.');
});

test('blocks car booking when total commission is missing', function () {
    $payment = makePayment(['commission' => null]);

    expect(fn () => app(BookingValidationService::class)->validate($payment, QuoteTypeId::Car))
        ->toThrow(BookingValidationException::class, 'Total commission is required.');
});

test('does not apply car commission validation for non-car quote type', function () {
    $payment = makePayment(['commmission_percentage' => null, 'commission_vat' => null, 'commission' => null]);

    expect(fn () => app(BookingValidationService::class)->validate($payment, QuoteTypeId::Health))
        ->not->toThrow(BookingValidationException::class);
});

test('exception message includes all collected errors', function () {
    $payment = makePayment([
        'total_price' => 0,
        'payment_methods_code' => PaymentMethodsEnum::CreditCard,
        'insurer_invoice_date' => null,
        'insurer_tax_number' => null,
    ]);

    try {
        app(BookingValidationService::class)->validate($payment, QuoteTypeId::Car);
        expect(false)->toBeTrue('Expected BookingValidationException was not thrown');
    } catch (BookingValidationException $e) {
        expect($e->errors)->toHaveCount(3)
            ->and($e->getMessage())->toContain('Policy amount must be greater than 0.')
            ->and($e->getMessage())->toContain('Insurer Invoice date is required.')
            ->and($e->getMessage())->toContain('Insurer tax invoice number is required.');
    }
});
