<?php

declare(strict_types=1);

use App\Enums\PaymentFrequency;
use App\Factories\SagePayloadFactory;
use App\Services\SageApiService;
use Carbon\Carbon;

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

describe('SagePayloadFactory reversal payment schedule transformations', function () {
    function makeReversalPayloadWithSchedules(int $scheduleCount): object
    {
        $schedules = [];
        $pastDates = ['2025-07-30', '2025-08-30', '2025-09-30'];
        for ($i = 0; $i < $scheduleCount; $i++) {
            $schedule = new stdClass;
            $schedule->DueDate = $pastDates[$i] ?? '2025-10-30';
            $schedules[] = $schedule;
        }

        $invoice = new stdClass;
        $invoice->DocumentNumber = 'INV-001';
        $invoice->InvoiceDescription = 'Test Invoice';
        $invoice->DocumentDate = '2025-07-30';
        $invoice->PostingDate = '2025-07-30';
        $invoice->DueDate = '2025-07-30';
        $invoice->AsOfDate = '2025-07-30';
        $invoice->InvoicePaymentSchedules = $schedules;

        $payload = new stdClass;
        $payload->Invoices = [$invoice];

        return $payload;
    }

    function makeReversalRequest(string $bookingDate): object
    {
        $req = new stdClass;
        $req->bookingDate = $bookingDate;

        return $req;
    }

    test('applyReversalTransformations sets all payment schedule due dates to booking date', function () {
        $payload = makeReversalPayloadWithSchedules(3);
        $request = makeReversalRequest('2026-05-18');
        $expected = Carbon::parse('2026-05-18')->format(config('constants.SAGE_300_API_DATE_FORMAT'));

        $method = new ReflectionMethod(SagePayloadFactory::class, 'applyReversalTransformations');
        $result = $method->invoke(null, $payload, $request, 0);

        foreach ($result->Invoices[0]->InvoicePaymentSchedules as $schedule) {
            expect($schedule->DueDate)->toBe($expected);
        }
    });

    test('applyReversalTransformations with single schedule still sets due date to booking date', function () {
        $payload = makeReversalPayloadWithSchedules(1);
        $request = makeReversalRequest('2026-05-18');
        $expected = Carbon::parse('2026-05-18')->format(config('constants.SAGE_300_API_DATE_FORMAT'));

        $method = new ReflectionMethod(SagePayloadFactory::class, 'applyReversalTransformations');
        $result = $method->invoke(null, $payload, $request, 0);

        expect($result->Invoices[0]->InvoicePaymentSchedules[0]->DueDate)->toBe($expected);
    });

    test('applyReversalTransformationsWithSplitPayments sets all payment schedule due dates to booking date', function () {
        $payload = makeReversalPayloadWithSchedules(3);
        $request = makeReversalRequest('2026-05-18');
        $expected = Carbon::parse('2026-05-18')->format(config('constants.SAGE_300_API_DATE_FORMAT'));

        $method = new ReflectionMethod(SagePayloadFactory::class, 'applyReversalTransformationsWithSplitPayments');
        // Pass empty splits — amounts are preserved from existing payload, only dates should change
        $result = $method->invoke(null, $payload, $request, [], 0);

        foreach ($result->Invoices[0]->InvoicePaymentSchedules as $schedule) {
            expect($schedule->DueDate)->toBe($expected);
        }
    });

    test('applyReversalTransformationsWithSplitPayments with fewer splits than schedules still updates all dates', function () {
        $payload = makeReversalPayloadWithSchedules(3);
        $request = makeReversalRequest('2026-05-18');
        $expected = Carbon::parse('2026-05-18')->format(config('constants.SAGE_300_API_DATE_FORMAT'));

        $method = new ReflectionMethod(SagePayloadFactory::class, 'applyReversalTransformationsWithSplitPayments');
        $result = $method->invoke(null, $payload, $request, [], 0);

        expect($result->Invoices[0]->InvoicePaymentSchedules)->toHaveCount(3);
        foreach ($result->Invoices[0]->InvoicePaymentSchedules as $schedule) {
            expect($schedule->DueDate)->toBe($expected);
        }
    });
});

describe('SagePayloadFactory::createPaymentSchedules', function () {
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
