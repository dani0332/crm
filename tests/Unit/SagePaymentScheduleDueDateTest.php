<?php

declare(strict_types=1);

use App\Enums\PaymentFrequency;
use App\Factories\SagePayloadFactory;
use App\Services\SageApiService;

describe('SageApiService::resolveInstallmentDueDateAgainstBookingDate', function () {
    test('returns booking date when installment due is earlier', function () {
        $service = app(SageApiService::class);

        expect($service->resolveInstallmentDueDateAgainstBookingDate('2026-04-01', '2026-04-10'))
            ->toBe('2026-04-10');
    });

    test('returns installment date when it is on or after booking', function () {
        $service = app(SageApiService::class);

        expect($service->resolveInstallmentDueDateAgainstBookingDate('2026-04-15', '2026-04-10'))
            ->toBe('2026-04-15');
    });

    test('returns same day when installment equals booking', function () {
        $service = app(SageApiService::class);

        expect($service->resolveInstallmentDueDateAgainstBookingDate('2026-04-10', '2026-04-10'))
            ->toBe('2026-04-10');
    });
});

describe('SagePayloadFactory::createPaymentSchedules', function () {
    /**
     * @return object{sr_no: int, due_date: string, payment_amount: float, payment: object}
     */
    function makeSplitItem(int $srNo, string $dueDate, float $amount, string $frequency): object
    {
        $payment = new stdClass;
        $payment->frequency = $frequency;

        $item = new stdClass;
        $item->sr_no = $srNo;
        $item->due_date = $dueDate;
        $item->payment_amount = $amount;
        $item->payment = $payment;

        return $item;
    }

    test('uses booking date when payment frequency is split_payments', function () {
        $booking = '2026-04-10';
        $rows = SagePayloadFactory::createPaymentSchedules([
            makeSplitItem(2, '2026-04-01', 50.0, PaymentFrequency::SPLIT_PAYMENTS),
        ], $booking);

        expect($rows[0]['DueDate'])->toBe($booking);
    });

    test('uses booking date for first schedule row when not split_payments', function () {
        $booking = '2026-04-10';
        $rows = SagePayloadFactory::createPaymentSchedules([
            makeSplitItem(1, '2026-04-01', 100.0, PaymentFrequency::MONTHLY),
        ], $booking);

        expect($rows[0]['DueDate'])->toBe($booking);
    });

    test('clamps later row due date to booking when installment is earlier', function () {
        $booking = '2026-04-10';
        $rows = SagePayloadFactory::createPaymentSchedules([
            makeSplitItem(2, '2026-04-01', 50.0, PaymentFrequency::MONTHLY),
        ], $booking);

        expect($rows[0]['DueDate'])->toBe($booking);
    });

    test('keeps installment due date when it is after booking', function () {
        $booking = '2026-04-10';
        $rows = SagePayloadFactory::createPaymentSchedules([
            makeSplitItem(2, '2026-04-20', 50.0, PaymentFrequency::MONTHLY),
        ], $booking);

        expect($rows[0]['DueDate'])->toBe('2026-04-20');
    });

    test('delegates non-first row due dates to SageApiService from the container', function () {
        $mock = Mockery::mock(SageApiService::class);
        $mock->shouldReceive('resolveInstallmentDueDateAgainstBookingDate')
            ->once()
            ->with('2026-04-01', '2026-04-10')
            ->andReturn('2026-04-10');
        app()->instance(SageApiService::class, $mock);

        $rows = SagePayloadFactory::createPaymentSchedules([
            makeSplitItem(2, '2026-04-01', 50.0, PaymentFrequency::MONTHLY),
        ], '2026-04-10');

        expect($rows[0]['DueDate'])->toBe('2026-04-10');
    });
});
