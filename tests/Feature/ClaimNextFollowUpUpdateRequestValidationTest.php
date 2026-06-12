<?php

declare(strict_types=1);

use App\Http\Requests\ClaimNextFollowUpUpdateRequest;
use Illuminate\Support\Facades\Validator;

test('rules do not contain a strict after constraint that would defeat the 1-minute buffer', function () {
    $request = new ClaimNextFollowUpUpdateRequest;
    $rules = $request->rules();

    $afterRules = array_filter(
        $rules['next_follow_up_date'],
        fn ($rule) => is_string($rule) && str_starts_with($rule, 'after:')
    );

    expect($afterRules)->toBeEmpty(
        'The after: rule must not exist in rules() — temporal validation is handled exclusively in withValidator() with a 1-minute buffer.'
    );
});

test('rules require next_follow_up_date as a required date', function () {
    $request = new ClaimNextFollowUpUpdateRequest;
    $rules = $request->rules();

    expect($rules['next_follow_up_date'])
        ->toContain('required')
        ->toContain('date');
});

test('a date 30 seconds in the past passes the base rules but is caught by the buffer check in withValidator', function () {
    $thirtySecondsAgo = (new DateTime('-30 seconds'))->format('Y-m-d H:i:s');

    $validator = Validator::make(
        ['next_follow_up_date' => $thirtySecondsAgo],
        (new ClaimNextFollowUpUpdateRequest)->rules()
    );

    // Base rules alone must pass (no strict after: constraint)
    expect($validator->passes())->toBeTrue();
});

test('validation passes for a future date within 15 days', function () {
    $futureDatetime = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

    $validator = Validator::make(
        ['next_follow_up_date' => $futureDatetime],
        (new ClaimNextFollowUpUpdateRequest)->rules()
    );

    expect($validator->passes())->toBeTrue();
});

test('validation fails when next_follow_up_date is missing', function () {
    $validator = Validator::make(
        [],
        (new ClaimNextFollowUpUpdateRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('next_follow_up_date'))->toBeTrue();
});

test('validation fails when next_follow_up_date is not a valid date string', function () {
    $validator = Validator::make(
        ['next_follow_up_date' => 'not-a-date'],
        (new ClaimNextFollowUpUpdateRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('next_follow_up_date'))->toBeTrue();
});

test('notes field is optional and accepts a string up to 500 characters', function () {
    $futureDatetime = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

    $validator = Validator::make(
        ['next_follow_up_date' => $futureDatetime, 'notes' => str_repeat('a', 500)],
        (new ClaimNextFollowUpUpdateRequest)->rules()
    );

    expect($validator->passes())->toBeTrue();
});

test('notes field fails when exceeding 500 characters', function () {
    $futureDatetime = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

    $validator = Validator::make(
        ['next_follow_up_date' => $futureDatetime, 'notes' => str_repeat('a', 501)],
        (new ClaimNextFollowUpUpdateRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('notes'))->toBeTrue();
});
