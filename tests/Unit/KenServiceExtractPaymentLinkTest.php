<?php

use App\Services\KenService;

test('extractPaymentLink reads top level keys', function () {
    expect(KenService::extractPaymentLink(['paymentLink' => 'https://pay.example/p']))->toBe('https://pay.example/p');
    expect(KenService::extractPaymentLink(['payment_url' => 'https://pay.example/u']))->toBe('https://pay.example/u');
});

test('extractPaymentLink reads nested data', function () {
    expect(KenService::extractPaymentLink([
        'data' => ['paymentLink' => 'https://nested.example'],
    ]))->toBe('https://nested.example');
});

test('extractPaymentLink returns null when missing', function () {
    expect(KenService::extractPaymentLink([]))->toBeNull();
    expect(KenService::extractPaymentLink(null))->toBeNull();
});
