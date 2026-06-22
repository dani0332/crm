<?php

use App\Enums\PaymentMethodsEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\HealthQuote;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Models\User;
use App\Services\HealthQuoteService;
use Illuminate\Support\Facades\Auth;

it('returns true when splits are all insurer payment and total_payments matches split count', function () {
    $payment = new Payment(['total_payments' => 2, 'send_update_log_id' => null]);
    $payment->setRelation('paymentSplits', collect([
        new PaymentSplits(['payment_method' => PaymentMethodsEnum::InsurerPayment]),
        new PaymentSplits(['payment_method' => PaymentMethodsEnum::InsurerPayment]),
    ]));

    $quote = Mockery::mock(HealthQuote::class)->makePartial();
    $quote->quote_status_id = QuoteStatusEnum::TransactionApproved;
    $quote->shouldReceive('payments')->never();

    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->with(PermissionsEnum::EDIT_PLAN_AFTER_TRANSACTION_APPROVAL)->once()->andReturn(true);

    Auth::shouldReceive('user')->once()->andReturn($user);

    $service = app(HealthQuoteService::class);

    expect($service->canBypassPlanLock($quote, collect([$payment])))->toBeTrue();
});

it('returns false when quote status is not transaction approved', function () {
    $quote = Mockery::mock(HealthQuote::class)->makePartial();
    $quote->quote_status_id = QuoteStatusEnum::Quoted;
    $quote->shouldReceive('payments')->never();

    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->with(PermissionsEnum::EDIT_PLAN_AFTER_TRANSACTION_APPROVAL)->once()->andReturn(true);

    Auth::shouldReceive('user')->once()->andReturn($user);

    $service = app(HealthQuoteService::class);

    expect($service->canBypassPlanLock($quote))->toBeFalse();
});

it('returns false when user lacks permission', function () {
    $quote = Mockery::mock(HealthQuote::class)->makePartial();
    $quote->quote_status_id = QuoteStatusEnum::TransactionApproved;
    $quote->shouldReceive('payments')->never();

    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->with(PermissionsEnum::EDIT_PLAN_AFTER_TRANSACTION_APPROVAL)->once()->andReturn(false);

    Auth::shouldReceive('user')->once()->andReturn($user);

    $service = app(HealthQuoteService::class);

    expect($service->canBypassPlanLock($quote))->toBeFalse();
});

it('returns false when any split is not insurer payment', function () {
    $payment = new Payment(['total_payments' => 2, 'send_update_log_id' => null]);
    $payment->setRelation('paymentSplits', collect([
        new PaymentSplits(['payment_method' => PaymentMethodsEnum::InsurerPayment]),
        new PaymentSplits(['payment_method' => PaymentMethodsEnum::CreditCard]),
    ]));

    $quote = Mockery::mock(HealthQuote::class)->makePartial();
    $quote->quote_status_id = QuoteStatusEnum::TransactionApproved;
    $quote->shouldReceive('payments')->never();

    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->with(PermissionsEnum::EDIT_PLAN_AFTER_TRANSACTION_APPROVAL)->once()->andReturn(true);

    Auth::shouldReceive('user')->once()->andReturn($user);

    $service = app(HealthQuoteService::class);

    expect($service->canBypassPlanLock($quote, collect([$payment])))->toBeFalse();
});

it('returns false when total_payments is null even if splits exist', function () {
    $payment = new Payment(['total_payments' => null, 'send_update_log_id' => null]);
    $payment->setRelation('paymentSplits', collect([
        new PaymentSplits(['payment_method' => PaymentMethodsEnum::InsurerPayment]),
    ]));

    $quote = Mockery::mock(HealthQuote::class)->makePartial();
    $quote->quote_status_id = QuoteStatusEnum::TransactionApproved;
    $quote->shouldReceive('payments')->never();

    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->with(PermissionsEnum::EDIT_PLAN_AFTER_TRANSACTION_APPROVAL)->once()->andReturn(true);

    Auth::shouldReceive('user')->once()->andReturn($user);

    $service = app(HealthQuoteService::class);

    expect($service->canBypassPlanLock($quote, collect([$payment])))->toBeFalse();
});

it('returns false when total_payments does not match split count', function () {
    $payment = new Payment(['total_payments' => 3, 'send_update_log_id' => null]);
    $payment->setRelation('paymentSplits', collect([
        new PaymentSplits(['payment_method' => PaymentMethodsEnum::InsurerPayment]),
        new PaymentSplits(['payment_method' => PaymentMethodsEnum::InsurerPayment]),
    ]));

    $quote = Mockery::mock(HealthQuote::class)->makePartial();
    $quote->quote_status_id = QuoteStatusEnum::TransactionApproved;
    $quote->shouldReceive('payments')->never();

    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->with(PermissionsEnum::EDIT_PLAN_AFTER_TRANSACTION_APPROVAL)->once()->andReturn(true);

    Auth::shouldReceive('user')->once()->andReturn($user);

    $service = app(HealthQuoteService::class);

    expect($service->canBypassPlanLock($quote, collect([$payment])))->toBeFalse();
});

it('returns false when payment has no splits', function () {
    $payment = new Payment(['total_payments' => 1, 'send_update_log_id' => 1]);
    $payment->setRelation('paymentSplits', collect());

    $quote = Mockery::mock(HealthQuote::class)->makePartial();
    $quote->quote_status_id = QuoteStatusEnum::TransactionApproved;
    $quote->shouldReceive('payments')->never();

    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->with(PermissionsEnum::EDIT_PLAN_AFTER_TRANSACTION_APPROVAL)->once()->andReturn(true);

    Auth::shouldReceive('user')->once()->andReturn($user);

    $service = app(HealthQuoteService::class);

    expect($service->canBypassPlanLock($quote, collect([$payment])))->toBeFalse();
});

it('returns false when quote is not a health quote and payments are not passed', function () {
    $quote = (object) [
        'quote_status_id' => QuoteStatusEnum::TransactionApproved,
    ];

    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->with(PermissionsEnum::EDIT_PLAN_AFTER_TRANSACTION_APPROVAL)->once()->andReturn(true);

    Auth::shouldReceive('user')->once()->andReturn($user);

    $service = app(HealthQuoteService::class);

    expect($service->canBypassPlanLock($quote))->toBeFalse();
});
