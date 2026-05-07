<?php

declare(strict_types=1);

use App\Enums\PaymentStatusEnum;
use App\Models\CustomerMembers;
use App\Models\Payment;
use App\Models\TravelQuote;
use App\Services\TravelQuoteService;
use Carbon\Carbon;
use Laravel\Telescope\Telescope;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

/**
 * @param  array<int, Payment>  $payments
 */
function travelMemberDelete_quoteWithPayments(array $payments): TravelQuote
{
    $quote = Mockery::mock(TravelQuote::class);
    $relation = Mockery::mock();
    $quote->shouldReceive('payments')->andReturn($relation);
    $relation->shouldReceive('mainLeadPayment')->andReturnSelf();
    $relation->shouldReceive('get')->andReturn(collect($payments));

    return $quote;
}

function travelMemberDelete_memberWithDob(string $dob): CustomerMembers
{
    $member = Mockery::mock(CustomerMembers::class);
    $member->shouldReceive('getRawOriginal')->with('dob')->andReturn($dob);

    return $member;
}

beforeEach(function () {
    if (class_exists(Telescope::class)) {
        Telescope::stopRecording();
    }

    Carbon::setTestNow('2026-04-01');
    TestSchemaCreator::ensureMinimalSchema();
    $this->actingAs(TestDataSeeder::createAdminUser());
});

afterEach(function () {
    Carbon::setTestNow();
});

describe('TravelQuoteService::memberHasAuthorizedPayment', function () {
    it('returns false when the quote has no payments', function () {
        $service = app(TravelQuoteService::class);
        $quote = travelMemberDelete_quoteWithPayments([]);

        expect($service->memberHasAuthorizedPayment(travelMemberDelete_memberWithDob('1990-01-01'), $quote))->toBeFalse();
    });

    it('returns false when no payment matches the non-senior segment', function () {
        $service = app(TravelQuoteService::class);
        $payment = new Payment([
            'code' => 'TRA-100-1',
            'payment_status_id' => PaymentStatusEnum::AUTHORISED,
        ]);
        $quote = travelMemberDelete_quoteWithPayments([$payment]);

        expect($service->memberHasAuthorizedPayment(travelMemberDelete_memberWithDob('1990-01-01'), $quote))->toBeFalse();
    });

    it('returns true when a non-senior linked parent payment is authorized', function () {
        $service = app(TravelQuoteService::class);
        $payment = new Payment([
            'code' => 'TRA-100',
            'payment_status_id' => PaymentStatusEnum::AUTHORISED,
        ]);
        $quote = travelMemberDelete_quoteWithPayments([$payment]);

        expect($service->memberHasAuthorizedPayment(travelMemberDelete_memberWithDob('1990-01-01'), $quote))->toBeTrue();
    });

    it('returns false when a non-senior linked parent payment is not authorized', function () {
        $service = app(TravelQuoteService::class);
        $payment = new Payment([
            'code' => 'TRA-100',
            'payment_status_id' => PaymentStatusEnum::NEW,
        ]);
        $quote = travelMemberDelete_quoteWithPayments([$payment]);

        expect($service->memberHasAuthorizedPayment(travelMemberDelete_memberWithDob('1990-01-01'), $quote))->toBeFalse();
    });

    it('returns true when a senior linked child payment (-1) is in an authorized status', function () {
        $service = app(TravelQuoteService::class);
        $payment = new Payment([
            'code' => 'TRA-100-1',
            'payment_status_id' => PaymentStatusEnum::CAPTURED,
        ]);
        $quote = travelMemberDelete_quoteWithPayments([$payment]);

        expect($service->memberHasAuthorizedPayment(travelMemberDelete_memberWithDob('1950-01-01'), $quote))->toBeTrue();
    });

    it('returns false when a senior linked child payment (-1) is not authorized', function () {
        $service = app(TravelQuoteService::class);
        $payment = new Payment([
            'code' => 'TRA-SDFSWSC-1',
            'payment_status_id' => PaymentStatusEnum::DECLINED,
        ]);
        $quote = travelMemberDelete_quoteWithPayments([$payment]);

        expect($service->memberHasAuthorizedPayment(travelMemberDelete_memberWithDob('1950-01-01'), $quote))->toBeFalse();
    });
});

test('travel member delete validation fails when the traveler record does not exist', function () {
    $this->deleteJson(route('travelers.destroy', ['traveler' => 999999999]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['travel_member_delete'])
        ->assertJsonPath('errors.travel_member_delete.0', 'The selected member could not be found.');
});
