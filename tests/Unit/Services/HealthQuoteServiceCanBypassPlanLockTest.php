<?php

use App\Enums\PaymentMethodsEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\HealthQuote;
use App\Models\Payment;
use App\Models\User;
use App\Services\HealthQuoteService;
use Illuminate\Support\Facades\Auth;

it('returns true when pre-loaded payments include main insurer payment', function () {
    $payment = Mockery::mock(Payment::class)->makePartial();
    $payment->payment_methods_code = PaymentMethodsEnum::InsurerPayment;
    $payment->send_update_log_id = null;

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

it('returns false when main payment is not insurer payment', function () {
    $payment = Mockery::mock(Payment::class)->makePartial();
    $payment->payment_methods_code = PaymentMethodsEnum::CreditCard;
    $payment->send_update_log_id = null;

    $quote = Mockery::mock(HealthQuote::class)->makePartial();
    $quote->quote_status_id = QuoteStatusEnum::TransactionApproved;
    $quote->shouldReceive('payments')->never();

    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->with(PermissionsEnum::EDIT_PLAN_AFTER_TRANSACTION_APPROVAL)->once()->andReturn(true);

    Auth::shouldReceive('user')->once()->andReturn($user);

    $service = app(HealthQuoteService::class);

    expect($service->canBypassPlanLock($quote, collect([$payment])))->toBeFalse();
});

it('returns false when there is no main lead payment in pre-loaded payments', function () {
    $payment = Mockery::mock(Payment::class)->makePartial();
    $payment->send_update_log_id = 1;

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
