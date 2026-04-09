<?php

use App\Http\Requests\UpdatePaymentRequest;
use Illuminate\Support\Facades\Validator;

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
