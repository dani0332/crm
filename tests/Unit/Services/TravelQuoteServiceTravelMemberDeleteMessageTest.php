<?php

declare(strict_types=1);

use App\Enums\PaymentStatusEnum;
use App\Models\CustomerMembers;
use App\Models\Payment;
use App\Models\TravelQuote;
use App\Services\TravelQuoteService;
use Carbon\Carbon;

beforeEach(function () {
    Carbon::setTestNow('2026-04-01');
});

afterEach(function () {
    Carbon::setTestNow();
});

function travelQuoteWithPayments(array $payments): TravelQuote
{
    $quote = Mockery::mock(TravelQuote::class);
    $relation = Mockery::mock();
    $quote->shouldReceive('payments')->andReturn($relation);
    $relation->shouldReceive('get')->andReturn(collect($payments));

    return $quote;
}

function memberWithDob(string $dob): CustomerMembers
{
    $member = Mockery::mock(CustomerMembers::class);
    $member->shouldReceive('getRawOriginal')->with('dob')->andReturn($dob);

    return $member;
}

function nonSeniorMemberDob(): string
{
    return '1990-01-01';
}

function seniorMemberDob(): string
{
    return '1950-01-01';
}

it('returns false when the quote has no payments', function () {
    $service = app(TravelQuoteService::class);
    $quote = travelQuoteWithPayments([]);

    expect($service->memberHasAuthorizedPayment(memberWithDob(nonSeniorMemberDob()), $quote))->toBeFalse();
});

it('returns false when no payment matches the member segment', function () {
    $service = app(TravelQuoteService::class);
    $payment = new Payment([
        'code' => 'TRA-100-1',
        'payment_status_id' => PaymentStatusEnum::AUTHORISED,
    ]);
    $quote = travelQuoteWithPayments([$payment]);

    expect($service->memberHasAuthorizedPayment(memberWithDob(nonSeniorMemberDob()), $quote))->toBeFalse();
});

it('returns true when a non-senior segment payment is authorized', function () {
    $service = app(TravelQuoteService::class);
    $payment = new Payment([
        'code' => 'TRA-100',
        'payment_status_id' => PaymentStatusEnum::AUTHORISED,
    ]);
    $quote = travelQuoteWithPayments([$payment]);

    expect($service->memberHasAuthorizedPayment(memberWithDob(nonSeniorMemberDob()), $quote))->toBeTrue();
});

it('returns true when a senior segment payment is authorized', function () {
    $service = app(TravelQuoteService::class);
    $payment = new Payment([
        'code' => 'TRA-100-1',
        'payment_status_id' => PaymentStatusEnum::CAPTURED,
    ]);
    $quote = travelQuoteWithPayments([$payment]);

    expect($service->memberHasAuthorizedPayment(memberWithDob(seniorMemberDob()), $quote))->toBeTrue();
});

it('returns false when relevant payments are not in an authorized status', function () {
    $service = app(TravelQuoteService::class);
    $payment = new Payment([
        'code' => 'TRA-100',
        'payment_status_id' => PaymentStatusEnum::NEW,
    ]);
    $quote = travelQuoteWithPayments([$payment]);

    expect($service->memberHasAuthorizedPayment(memberWithDob(nonSeniorMemberDob()), $quote))->toBeFalse();
});
