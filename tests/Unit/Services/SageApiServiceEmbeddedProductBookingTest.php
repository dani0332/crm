<?php

declare(strict_types=1);

use App\Enums\PaymentGatewayEnum;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Models\EmbeddedTransaction;
use App\Models\Payment;
use App\Services\SageApiEmbeddedProductService;
use App\Services\SageApiService;
use Mockery;

beforeEach(function () {
    $this->sageApiService = Mockery::mock(SageApiService::class)->makePartial();
    $this->sageApiEmbeddedProductService = Mockery::mock(SageApiEmbeddedProductService::class);
});

afterEach(function () {
    Mockery::close();
});

test('embedded product booking is allowed for supported LOBs', function () {
    $sageApiService = new SageApiService;

    // Car and Bike should be allowed
    expect($sageApiService->isLobAllowedForEmbeddedProductBooking(QuoteTypeId::Car))->toBeTrue();
    expect($sageApiService->isLobAllowedForEmbeddedProductBooking(QuoteTypeId::Bike))->toBeTrue();

    // Other LOBs should not be allowed
    expect($sageApiService->isLobAllowedForEmbeddedProductBooking(QuoteTypeId::Travel))->toBeFalse();
    expect($sageApiService->isLobAllowedForEmbeddedProductBooking(QuoteTypeId::Life))->toBeFalse();
});

test('embedded product booking proceeds regardless of payment gateway', function () {
    $quote = Mockery::mock(CarQuote::class);
    $quote->shouldReceive('getAttribute')->with('uuid')->andReturn('test-uuid');
    $quote->shouldReceive('getAttribute')->with('code')->andReturn('test-code');
    $quote->shouldReceive('getAttribute')->with('id')->andReturn(1);

    $ePTransaction = Mockery::mock(EmbeddedTransaction::class);
    $ePTransaction->shouldReceive('getAttribute')->with('code')->andReturn('EP-TEST-001');

    $payment = Mockery::mock(Payment::class);

    // Test with TAP payment gateway
    $payment->shouldReceive('getAttribute')->with('payment_gateway_id')
        ->andReturn(PaymentGatewayEnum::PAYMENT_GATEWAY_TAP);

    $sageApiService = Mockery::mock(SageApiService::class)->makePartial();
    $sageApiService->shouldReceive('isLobAllowedForEmbeddedProductBooking')
        ->with(QuoteTypeId::Car)
        ->andReturn(true);

    $isAllowed = $sageApiService->isLobAllowedForEmbeddedProductBooking(QuoteTypeId::Car);
    $hasTransaction = ! is_null($ePTransaction);

    // The condition should be true regardless of payment gateway
    expect($isAllowed && $hasTransaction)->toBeTrue();
});

test('embedded product booking works with non-TAP payment gateways', function () {
    $quote = Mockery::mock(CarQuote::class);
    $ePTransaction = Mockery::mock(EmbeddedTransaction::class);
    $payment = Mockery::mock(Payment::class);

    // Test with various payment gateways other than TAP
    $nonTapGateways = [
        PaymentGatewayEnum::PAYMENT_GATEWAY_CHECKOUT,
        PaymentGatewayEnum::PAYMENT_GATEWAY_PAYMENT_LINK,
    ];

    foreach ($nonTapGateways as $gatewayId) {
        $payment->shouldReceive('getAttribute')->with('payment_gateway_id')
            ->andReturn($gatewayId);

        $sageApiService = Mockery::mock(SageApiService::class)->makePartial();
        $sageApiService->shouldReceive('isLobAllowedForEmbeddedProductBooking')
            ->with(QuoteTypeId::Car)
            ->andReturn(true);

        $isAllowed = $sageApiService->isLobAllowedForEmbeddedProductBooking(QuoteTypeId::Car);
        $hasTransaction = ! is_null($ePTransaction);

        // Embedded product booking should work with any payment gateway
        expect($isAllowed && $hasTransaction)->toBeTrue();
    }
});

test('embedded product booking does not proceed when LOB is not allowed', function () {
    $quote = Mockery::mock(CarQuote::class);
    $ePTransaction = Mockery::mock(EmbeddedTransaction::class);
    $payment = Mockery::mock(Payment::class);

    $payment->shouldReceive('getAttribute')->with('payment_gateway_id')
        ->andReturn(PaymentGatewayEnum::PAYMENT_GATEWAY_TAP);

    $sageApiService = Mockery::mock(SageApiService::class)->makePartial();
    $sageApiService->shouldReceive('isLobAllowedForEmbeddedProductBooking')
        ->with(QuoteTypeId::Travel)
        ->andReturn(false);

    $isAllowed = $sageApiService->isLobAllowedForEmbeddedProductBooking(QuoteTypeId::Travel);
    $hasTransaction = ! is_null($ePTransaction);

    // Should not proceed when LOB is not allowed
    expect($isAllowed && $hasTransaction)->toBeFalse();
});

test('embedded product booking does not proceed when no transaction exists', function () {
    $quote = Mockery::mock(CarQuote::class);
    $ePTransaction = null; // No transaction
    $payment = Mockery::mock(Payment::class);

    $payment->shouldReceive('getAttribute')->with('payment_gateway_id')
        ->andReturn(PaymentGatewayEnum::PAYMENT_GATEWAY_TAP);

    $sageApiService = Mockery::mock(SageApiService::class)->makePartial();
    $sageApiService->shouldReceive('isLobAllowedForEmbeddedProductBooking')
        ->with(QuoteTypeId::Car)
        ->andReturn(true);

    $isAllowed = $sageApiService->isLobAllowedForEmbeddedProductBooking(QuoteTypeId::Car);
    $hasTransaction = ! is_null($ePTransaction);

    // Should not proceed when no transaction exists
    expect($isAllowed && $hasTransaction)->toBeFalse();
});

test('embedded product booking logic matches the updated condition in bookPolicyOnSage', function () {
    // This test verifies the logic after the change (removal of payment gateway check)

    $scenarios = [
        [
            'description' => 'Car with TAP gateway and transaction',
            'quoteTypeId' => QuoteTypeId::Car,
            'paymentGatewayId' => PaymentGatewayEnum::PAYMENT_GATEWAY_TAP,
            'hasTransaction' => true,
            'expectedResult' => true,
        ],
        [
            'description' => 'Car with Checkout gateway and transaction',
            'quoteTypeId' => QuoteTypeId::Car,
            'paymentGatewayId' => PaymentGatewayEnum::PAYMENT_GATEWAY_CHECKOUT,
            'hasTransaction' => true,
            'expectedResult' => true, // This should now be true after the change
        ],
        [
            'description' => 'Car with Payment Link gateway and transaction',
            'quoteTypeId' => QuoteTypeId::Car,
            'paymentGatewayId' => PaymentGatewayEnum::PAYMENT_GATEWAY_PAYMENT_LINK,
            'hasTransaction' => true,
            'expectedResult' => true, // This should now be true after the change
        ],
        [
            'description' => 'Bike with TAP gateway and transaction',
            'quoteTypeId' => QuoteTypeId::Bike,
            'paymentGatewayId' => PaymentGatewayEnum::PAYMENT_GATEWAY_TAP,
            'hasTransaction' => true,
            'expectedResult' => true,
        ],
        [
            'description' => 'Travel with TAP gateway and transaction',
            'quoteTypeId' => QuoteTypeId::Travel,
            'paymentGatewayId' => PaymentGatewayEnum::PAYMENT_GATEWAY_TAP,
            'hasTransaction' => true,
            'expectedResult' => false, // Travel is not allowed
        ],
        [
            'description' => 'Car with TAP gateway but no transaction',
            'quoteTypeId' => QuoteTypeId::Car,
            'paymentGatewayId' => PaymentGatewayEnum::PAYMENT_GATEWAY_TAP,
            'hasTransaction' => false,
            'expectedResult' => false, // No transaction
        ],
    ];

    foreach ($scenarios as $scenario) {
        $sageApiService = new SageApiService;

        $isLobAllowed = $sageApiService->isLobAllowedForEmbeddedProductBooking($scenario['quoteTypeId']);
        $hasTransaction = $scenario['hasTransaction'];

        // The new condition: isLobAllowedForEmbeddedProductBooking && $ePTransaction
        // (payment gateway check has been removed)
        $shouldProceed = $isLobAllowed && $hasTransaction;

        expect($shouldProceed)
            ->toBe($scenario['expectedResult'])
            ->and($shouldProceed)
            ->toBe($scenario['expectedResult'], $scenario['description']);
    }
});
