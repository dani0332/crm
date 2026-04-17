<?php

use App\Services\CustomerEmailMaskingService;

beforeEach(function () {
    $this->service = app(CustomerEmailMaskingService::class);
});

test('returns null for empty or invalid email', function () {
    expect($this->service->maskPurchaseEmailForDisplay(null))->toBeNull()
        ->and($this->service->maskPurchaseEmailForDisplay(''))->toBeNull()
        ->and($this->service->maskPurchaseEmailForDisplay('not-an-email'))->toBeNull();
});

test('masks local part per FRD examples for length >= 5', function (string $input, string $expected) {
    expect($this->service->maskPurchaseEmailForDisplay($input))->toBe($expected);
})->with([
    'evelet' => ['evelet@gmail.com', 'ev**et@gmail.com'],
    'johnsmith' => ['johnsmith@yahoo.com', 'jo*****th@yahoo.com'],
    'abraham' => ['abraham@test.com', 'ab***am@test.com'],
]);

test('masks edge cases for username shorter than four characters', function (string $input, string $expected) {
    expect($this->service->maskPurchaseEmailForDisplay($input))->toBe($expected);
})->with([
    'two chars' => ['ab@abc.com', 'a*@abc.com'],
    'three chars' => ['abc@abc.com', 'a**@abc.com'],
]);

test('masks four character username with first char and three asterisks', function () {
    expect($this->service->maskPurchaseEmailForDisplay('abcd@abc.com'))->toBe('a***@abc.com');
});
