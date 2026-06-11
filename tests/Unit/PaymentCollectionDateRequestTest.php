<?php

use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\CarQuote;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\NestedRules;

test('update payment request rejects collection dates before today', function () {
    $rule = (new UpdatePaymentRequest)->rules()['payment.collection_date'];

    $validator = Validator::make(
        ['payment' => ['collection_date' => now()->subDay()->toDateString()]],
        ['payment.collection_date' => $rule]
    );

    expect($validator->fails())->toBeTrue();
});

test('update payment request accepts collection date of today', function () {
    $rule = (new UpdatePaymentRequest)->rules()['payment.collection_date'];

    $validator = Validator::make(
        ['payment' => ['collection_date' => now()->toDateString()]],
        ['payment.collection_date' => $rule]
    );

    expect($validator->passes())->toBeTrue();
});

test('update payment request rejects split due dates before today', function () {
    $rules = (new UpdatePaymentRequest)->rules();
    $dueRule = $rules['payment.payment_splits.*.due_date'];

    $validator = Validator::make(
        [
            'payment' => [
                'payment_splits' => [
                    ['due_date' => now()->subDay()->toDateString()],
                ],
            ],
        ],
        ['payment.payment_splits.*.due_date' => $dueRule]
    );

    expect($validator->fails())->toBeTrue();
});

test('update payment request accepts split due date of today', function () {
    $rules = (new UpdatePaymentRequest)->rules();
    $dueRule = $rules['payment.payment_splits.*.due_date'];

    $validator = Validator::make(
        [
            'payment' => [
                'payment_splits' => [
                    ['due_date' => now()->toDateString()],
                ],
            ],
        ],
        ['payment.payment_splits.*.due_date' => $dueRule]
    );

    expect($validator->passes())->toBeTrue();
});

test('update payment request allows past collection and split due dates when quote is Policy Booked', function (): void {
    $quote = CarQuote::factory()->create([
        'quote_status_id' => QuoteStatusEnum::PolicyBooked,
    ]);

    $baseRequest = Request::create('/', 'POST', [
        'modelType' => 'car',
        'quote_id' => $quote->id,
    ]);

    $formRequest = UpdatePaymentRequest::createFrom($baseRequest);
    $formRequest->setContainer(app());
    $formRequest->setRedirector(app('redirect'));

    $rules = $formRequest->rules();

    expect($rules['payment.collection_date'])->toBe('required|date');
    expect($rules['payment.payment_splits.*.due_date'])->toBeInstanceOf(NestedRules::class);

    $collectionValidator = Validator::make(
        ['payment' => ['collection_date' => now()->subDays(30)->toDateString()]],
        ['payment.collection_date' => $rules['payment.collection_date']]
    );
    expect($collectionValidator->passes())->toBeTrue();

    $splitValidator = Validator::make(
        [
            'payment' => [
                'payment_splits' => [
                    ['due_date' => now()->subDays(10)->toDateString()],
                ],
            ],
        ],
        ['payment.payment_splits.*.due_date' => $rules['payment.payment_splits.*.due_date']]
    );
    expect($splitValidator->passes())->toBeTrue();
});

test('update payment request allows past collection date when master payment has paid status', function (): void {
    $quote = CarQuote::factory()->create();
    $payment = Payment::factory()->create([
        'code' => $quote->code,
        'paymentable_id' => $quote->id,
        'paymentable_type' => CarQuote::class,
        'payment_status_id' => PaymentStatusEnum::CAPTURED,
    ]);

    $baseRequest = Request::create('/', 'POST', [
        'modelType' => 'car',
        'quote_id' => $quote->id,
        'paymentCode' => $payment->code,
    ]);

    $formRequest = UpdatePaymentRequest::createFrom($baseRequest);
    $formRequest->setContainer(app());
    $formRequest->setRedirector(app('redirect'));

    $rules = $formRequest->rules();

    expect($rules['payment.collection_date'])->toBe('required|date');

    $collectionValidator = Validator::make(
        ['payment' => ['collection_date' => now()->subDays(30)->toDateString()]],
        ['payment.collection_date' => $rules['payment.collection_date']]
    );

    expect($collectionValidator->passes())->toBeTrue();
});
