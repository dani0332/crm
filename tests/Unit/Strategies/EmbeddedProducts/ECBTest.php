<?php

declare(strict_types=1);

use App\Enums\CarPlanType;
use App\Enums\CarVehicleUse;
use App\Enums\EpEcbExcludeVehicleEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\CarQuote;
use App\Models\EmbeddedTransaction;
use App\Strategies\EmbeddedProducts\ECB;
use Carbon\Carbon;

describe('ECB', function () {
    describe('isDisabled', function () {
        test('returns true when preCheck is true (payment status is AUTHORISED)', function () {
            $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
            $epTransaction->payment_status_id = PaymentStatusEnum::AUTHORISED;
            $epTransaction->is_active = 1;
            $epTransaction->quoteRequest = null;

            $strategy = new ECB;

            expect($strategy->isDisabled($epTransaction))->toBeTrue();
        });

        test('returns true when preCheck is true (is_active is 0)', function () {
            $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
            $epTransaction->payment_status_id = PaymentStatusEnum::DRAFT;
            $epTransaction->is_active = 0;
            $epTransaction->quoteRequest = null;

            $strategy = new ECB;

            expect($strategy->isDisabled($epTransaction))->toBeTrue();
        });

        test('returns true when quoteRequest is null', function () {
            $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
            $epTransaction->payment_status_id = PaymentStatusEnum::DRAFT;
            $epTransaction->is_active = 1;
            $epTransaction->quoteRequest = null;

            $strategy = new ECB;

            expect($strategy->isDisabled($epTransaction))->toBeTrue();
        });

        test('returns true when quote is PolicyBooked and policy_booking_date is more than 30 days ago', function () {
            $quote = Mockery::mock(CarQuote::class)->makePartial();
            $quote->quote_status_id = QuoteStatusEnum::PolicyBooked;
            $quote->policy_booking_date = Carbon::now()->subDays(31)->toDateString();
            $quote->carMake = (object) ['code' => 'TOY'];
            $quote->carModel = (object) ['code' => 'CAM'];
            $quote->plan = (object) ['repair_type' => CarPlanType::COMP];
            $quote->vehicle_use = CarVehicleUse::PRIVATE;
            $quote->is_modified = false;

            $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
            $epTransaction->payment_status_id = PaymentStatusEnum::DRAFT;
            $epTransaction->is_active = 1;
            $epTransaction->quoteRequest = $quote;

            $strategy = new ECB;

            expect($strategy->isDisabled($epTransaction))->toBeTrue();
        });

        test('returns true when quote is PolicyBooked and policy_booking_date is invalid', function () {
            $quote = Mockery::mock(CarQuote::class)->makePartial();
            $quote->quote_status_id = QuoteStatusEnum::PolicyBooked;
            $quote->policy_booking_date = '';
            $quote->carMake = (object) ['code' => 'TOY'];
            $quote->carModel = (object) ['code' => 'CAM'];
            $quote->plan = (object) ['repair_type' => CarPlanType::COMP];
            $quote->vehicle_use = CarVehicleUse::PRIVATE;
            $quote->is_modified = false;

            $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
            $epTransaction->payment_status_id = PaymentStatusEnum::DRAFT;
            $epTransaction->is_active = 1;
            $epTransaction->quoteRequest = $quote;

            $strategy = new ECB;

            expect($strategy->isDisabled($epTransaction))->toBeTrue();
        });

        test('returns true when isCriteriaMatched is false (excluded car make)', function () {
            $quote = Mockery::mock(CarQuote::class)->makePartial();
            $quote->quote_status_id = QuoteStatusEnum::PolicyBooked;
            $quote->policy_booking_date = Carbon::now()->toDateString();
            $quote->carMake = (object) ['code' => EpEcbExcludeVehicleEnum::CAR_MAKE_CODES[0]];
            $quote->carModel = (object) ['code' => 'CAM'];
            $quote->plan = (object) ['repair_type' => CarPlanType::COMP];
            $quote->vehicle_use = CarVehicleUse::PRIVATE;
            $quote->is_modified = false;

            $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
            $epTransaction->payment_status_id = PaymentStatusEnum::DRAFT;
            $epTransaction->is_active = 1;
            $epTransaction->quoteRequest = $quote;

            $strategy = new ECB;

            expect($strategy->isDisabled($epTransaction))->toBeTrue();
        });

        test('returns true when isCriteriaMatched is false (TPL plan)', function () {
            $quote = Mockery::mock(CarQuote::class)->makePartial();
            $quote->quote_status_id = QuoteStatusEnum::PolicyBooked;
            $quote->policy_booking_date = Carbon::now()->toDateString();
            $quote->carMake = (object) ['code' => 'TOY'];
            $quote->carModel = (object) ['code' => 'CAM'];
            $quote->plan = (object) ['repair_type' => CarPlanType::TPL];
            $quote->vehicle_use = CarVehicleUse::PRIVATE;
            $quote->is_modified = false;

            $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
            $epTransaction->payment_status_id = PaymentStatusEnum::DRAFT;
            $epTransaction->is_active = 1;
            $epTransaction->quoteRequest = $quote;

            $strategy = new ECB;

            expect($strategy->isDisabled($epTransaction))->toBeTrue();
        });

        test('returns false when draft, active, criteria matched, and policy date within 30 days', function () {
            $quote = Mockery::mock(CarQuote::class)->makePartial();
            $quote->quote_status_id = QuoteStatusEnum::PolicyBooked;
            $quote->policy_booking_date = Carbon::now()->subDays(10)->toDateString();
            $quote->carMake = (object) ['code' => 'TOY'];
            $quote->carModel = (object) ['code' => 'CAM'];
            $quote->plan = (object) ['repair_type' => CarPlanType::COMP];
            $quote->vehicle_use = CarVehicleUse::PRIVATE;
            $quote->is_modified = false;

            $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
            $epTransaction->payment_status_id = PaymentStatusEnum::DRAFT;
            $epTransaction->is_active = 1;
            $epTransaction->quoteRequest = $quote;

            $strategy = new ECB;

            expect($strategy->isDisabled($epTransaction))->toBeFalse();
        });

        test('returns false when quote status is not PolicyBooked (policy date check skipped)', function () {
            $quote = Mockery::mock(CarQuote::class)->makePartial();
            $quote->quote_status_id = QuoteStatusEnum::Issued;
            $quote->policy_booking_date = Carbon::now()->subDays(60)->toDateString();
            $quote->carMake = (object) ['code' => 'TOY'];
            $quote->carModel = (object) ['code' => 'CAM'];
            $quote->plan = (object) ['repair_type' => CarPlanType::COMP];
            $quote->vehicle_use = CarVehicleUse::PRIVATE;
            $quote->is_modified = false;

            $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
            $epTransaction->payment_status_id = PaymentStatusEnum::DRAFT;
            $epTransaction->is_active = 1;
            $epTransaction->quoteRequest = $quote;

            $strategy = new ECB;

            expect($strategy->isDisabled($epTransaction))->toBeFalse();
        });

        test('returns false when criteria matched with is_modified null (legacy records)', function () {
            $quote = Mockery::mock(CarQuote::class)->makePartial();
            $quote->quote_status_id = QuoteStatusEnum::PolicyBooked;
            $quote->policy_booking_date = Carbon::now()->subDays(10)->toDateString();
            $quote->carMake = (object) ['code' => 'TOY'];
            $quote->carModel = (object) ['code' => 'CAM'];
            $quote->plan = (object) ['repair_type' => CarPlanType::COMP];
            $quote->vehicle_use = CarVehicleUse::PRIVATE;
            $quote->is_modified = null;

            $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
            $epTransaction->payment_status_id = PaymentStatusEnum::DRAFT;
            $epTransaction->is_active = 1;
            $epTransaction->quoteRequest = $quote;

            $strategy = new ECB;

            expect($strategy->isDisabled($epTransaction))->toBeFalse();
        });

        test('returns true when is_modified is true (modified vehicle not eligible)', function () {
            $quote = Mockery::mock(CarQuote::class)->makePartial();
            $quote->quote_status_id = QuoteStatusEnum::PolicyBooked;
            $quote->policy_booking_date = Carbon::now()->subDays(10)->toDateString();
            $quote->carMake = (object) ['code' => 'TOY'];
            $quote->carModel = (object) ['code' => 'CAM'];
            $quote->plan = (object) ['repair_type' => CarPlanType::COMP];
            $quote->vehicle_use = CarVehicleUse::PRIVATE;
            $quote->is_modified = true;

            $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
            $epTransaction->payment_status_id = PaymentStatusEnum::DRAFT;
            $epTransaction->is_active = 1;
            $epTransaction->quoteRequest = $quote;

            $strategy = new ECB;

            expect($strategy->isDisabled($epTransaction))->toBeTrue();
        });
    });
});

afterEach(function () {
    Mockery::close();
});
