<?php

/**
 * Device quote request validation tests. Any mocking uses Mockery only.
 */

use App\Http\Requests\DeviceQuoteRequest;
use Illuminate\Support\Facades\Validator;
use Mockery;

beforeEach(function () {
    $this->rules = (new DeviceQuoteRequest)->rules();
});

afterEach(function () {
    Mockery::close();
});

test('device quote request passes with valid data', function () {
    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'user@gmail.com',
        'mobile_no' => '+971501234567',
        'month_of_purchase' => '6',
        'year_of_purchase' => '2024',
        'make_id' => 1,
        'model_id' => 1,
        'imei' => '123456789012345',
    ];

    $validator = Validator::make($data, $this->rules);
    expect($validator->passes())->toBeTrue();
});

test('device quote request fails when first_name is missing', function () {
    $data = [
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'mobile_no' => '+971501234567',
        'month_of_purchase' => '6',
        'year_of_purchase' => '2024',
        'make_id' => 1,
        'model_id' => 1,
        'imei' => '123456789012345',
    ];

    $validator = Validator::make($data, $this->rules);
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('first_name'))->toBeTrue();
});

test('device quote request fails when imei is not 15 digits', function () {
    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'mobile_no' => '+971501234567',
        'month_of_purchase' => '6',
        'year_of_purchase' => '2024',
        'make_id' => 1,
        'model_id' => 1,
        'imei' => '123',
    ];

    $validator = Validator::make($data, $this->rules);
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('imei'))->toBeTrue();
});

test('device quote request fails when email is invalid', function () {
    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'not-an-email',
        'mobile_no' => '+971501234567',
        'month_of_purchase' => '6',
        'year_of_purchase' => '2024',
        'make_id' => 1,
        'model_id' => 1,
        'imei' => '123456789012345',
    ];

    $validator = Validator::make($data, $this->rules);
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue();
});
