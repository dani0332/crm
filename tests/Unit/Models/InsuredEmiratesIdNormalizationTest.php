<?php

declare(strict_types=1);

use App\Enums\CustomerTypeEnum;
use App\Models\Insured;
use App\Models\InsuredKyc;

beforeEach(function () {
    // Schema is set up globally in TestCase
});

describe('Insured Model - Emirates ID Normalization', function () {
    test('create() removes hyphens from Emirates ID', function () {
        $emiratesIdWithHyphens = '784-1985-1234567-1';
        $expectedEmiratesId = '784198512345671';

        $insured = Insured::create([
            'customer_type' => CustomerTypeEnum::Individual,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'id_type' => 'emiratesId',
            'id_number' => $emiratesIdWithHyphens,
        ]);

        expect($insured)->not->toBeNull()
            ->and($insured->getRawOriginal('id_number'))->toBe($expectedEmiratesId);
    });

    test('create() does not affect non-Emirates ID types', function () {
        $passportNumber = 'A12345678';

        $insured = Insured::create([
            'customer_type' => CustomerTypeEnum::Individual,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'id_type' => 'passport',
            'id_number' => $passportNumber,
        ]);

        expect($insured)->not->toBeNull()
            ->and($insured->getRawOriginal('id_number'))->toBe($passportNumber);
    });

    test('updateOrCreate() removes hyphens from Emirates ID in attributes', function () {
        $emiratesIdWithHyphens = '784-1985-1234567-1';
        $expectedEmiratesId = '784198512345671';

        $insured = Insured::updateOrCreate(
            [
                'id_type' => 'emiratesId',
                'id_number' => $emiratesIdWithHyphens,
            ],
            [
                'customer_type' => CustomerTypeEnum::Individual,
                'first_name' => 'John',
                'last_name' => 'Doe',
            ]
        );

        expect($insured)->not->toBeNull()
            ->and($insured->getRawOriginal('id_number'))->toBe($expectedEmiratesId);
    });

    test('updateOrCreate() removes hyphens from Emirates ID in values', function () {
        $emiratesIdWithHyphens = '784-1985-1234567-1';
        $expectedEmiratesId = '784198512345671';

        // Create initial record
        $insured = Insured::create([
            'customer_type' => CustomerTypeEnum::Individual,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'id_type' => 'emiratesId',
            'id_number' => '784198512345671',
        ]);

        // Update with hyphens in values
        $updated = Insured::updateOrCreate(
            [
                'id' => $insured->id,
            ],
            [
                'id_type' => 'emiratesId',
                'id_number' => $emiratesIdWithHyphens,
            ]
        );

        expect($updated)->not->toBeNull()
            ->and($updated->getRawOriginal('id_number'))->toBe($expectedEmiratesId);
    });

    test('updateOrCreate() removes hyphens from Emirates ID in both attributes and values', function () {
        $emiratesIdWithHyphens = '784-1985-1234567-1';
        $expectedEmiratesId = '784198512345671';

        $insured = Insured::updateOrCreate(
            [
                'id_type' => 'emiratesId',
                'id_number' => $emiratesIdWithHyphens,
            ],
            [
                'customer_type' => CustomerTypeEnum::Individual,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'id_type' => 'emiratesId',
                'id_number' => $emiratesIdWithHyphens,
            ]
        );

        expect($insured)->not->toBeNull()
            ->and($insured->getRawOriginal('id_number'))->toBe($expectedEmiratesId);
    });

    test('normalizes different hyphen patterns', function () {
        $testCases = [
            '784-1985-1234567-1',
            '784-1985-1234567-1-',
            '-784-1985-1234567-1',
            '784--1985--1234567--1',
        ];

        foreach ($testCases as $emiratesIdWithHyphens) {
            $expectedEmiratesId = '784198512345671';

            $insured = Insured::create([
                'customer_type' => CustomerTypeEnum::Individual,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'id_type' => 'emiratesId',
                'id_number' => $emiratesIdWithHyphens,
            ]);

            expect($insured->getRawOriginal('id_number'))
                ->toBe($expectedEmiratesId, "Failed to normalize: {$emiratesIdWithHyphens}");
        }
    });

    test('handles null id_number gracefully', function () {
        $insured = Insured::create([
            'customer_type' => CustomerTypeEnum::Individual,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'id_type' => 'emiratesId',
            'id_number' => null,
        ]);

        expect($insured)->not->toBeNull()
            ->and($insured->getRawOriginal('id_number'))->toBeNull();
    });

    test('handles non-string id_number gracefully', function () {
        $insured = Insured::create([
            'customer_type' => CustomerTypeEnum::Individual,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'id_type' => 'emiratesId',
            'id_number' => 123456789012345, // numeric value
        ]);

        expect($insured)->not->toBeNull()
            ->and($insured->getRawOriginal('id_number'))->not->toBeNull();
    });
});

describe('InsuredKyc Model - Emirates ID Normalization', function () {
    test('create() removes hyphens from Emirates ID', function () {
        $insured = Insured::create([
            'customer_type' => CustomerTypeEnum::Individual,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'id_type' => 'emiratesId',
            'id_number' => '784198512345671',
        ]);

        $emiratesIdWithHyphens = '784-1985-1234567-1';
        $expectedEmiratesId = '784198512345671';

        $insuredKyc = InsuredKyc::create([
            'insured_id' => $insured->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'id_type' => 'emiratesId',
            'id_number' => $emiratesIdWithHyphens,
        ]);

        expect($insuredKyc)->not->toBeNull()
            ->and($insuredKyc->getRawOriginal('id_number'))->toBe($expectedEmiratesId);
    });

    test('create() does not affect non-Emirates ID types', function () {
        $insured = Insured::create([
            'customer_type' => CustomerTypeEnum::Individual,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'id_type' => 'passport',
            'id_number' => 'A12345678',
        ]);

        $passportNumber = 'A12345678';

        $insuredKyc = InsuredKyc::create([
            'insured_id' => $insured->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'id_type' => 'passport',
            'id_number' => $passportNumber,
        ]);

        expect($insuredKyc)->not->toBeNull()
            ->and($insuredKyc->getRawOriginal('id_number'))->toBe($passportNumber);
    });

    test('updateOrCreate() removes hyphens from Emirates ID in attributes', function () {
        $insured = Insured::create([
            'customer_type' => CustomerTypeEnum::Individual,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'id_type' => 'emiratesId',
            'id_number' => '784198512345671',
        ]);

        $emiratesIdWithHyphens = '784-1985-1234567-1';
        $expectedEmiratesId = '784198512345671';

        $insuredKyc = InsuredKyc::updateOrCreate(
            [
                'insured_id' => $insured->id,
                'id_type' => 'emiratesId',
                'id_number' => $emiratesIdWithHyphens,
            ],
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
            ]
        );

        expect($insuredKyc)->not->toBeNull()
            ->and($insuredKyc->getRawOriginal('id_number'))->toBe($expectedEmiratesId);
    });

    test('updateOrCreate() removes hyphens from Emirates ID in values', function () {
        $insured = Insured::create([
            'customer_type' => CustomerTypeEnum::Individual,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'id_type' => 'emiratesId',
            'id_number' => '784198512345671',
        ]);

        // Create initial KYC record
        $insuredKyc = InsuredKyc::create([
            'insured_id' => $insured->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'id_type' => 'emiratesId',
            'id_number' => '784198512345671',
        ]);

        $emiratesIdWithHyphens = '784-1985-1234567-1';
        $expectedEmiratesId = '784198512345671';

        // Update with hyphens in values
        $updated = InsuredKyc::updateOrCreate(
            [
                'id' => $insuredKyc->id,
            ],
            [
                'id_type' => 'emiratesId',
                'id_number' => $emiratesIdWithHyphens,
            ]
        );

        expect($updated)->not->toBeNull()
            ->and($updated->getRawOriginal('id_number'))->toBe($expectedEmiratesId);
    });

    test('updateOrCreate() removes hyphens from Emirates ID in both attributes and values', function () {
        $insured = Insured::create([
            'customer_type' => CustomerTypeEnum::Individual,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'id_type' => 'emiratesId',
            'id_number' => '784198512345671',
        ]);

        $emiratesIdWithHyphens = '784-1985-1234567-1';
        $expectedEmiratesId = '784198512345671';

        $insuredKyc = InsuredKyc::updateOrCreate(
            [
                'insured_id' => $insured->id,
                'id_type' => 'emiratesId',
                'id_number' => $emiratesIdWithHyphens,
            ],
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'id_type' => 'emiratesId',
                'id_number' => $emiratesIdWithHyphens,
            ]
        );

        expect($insuredKyc)->not->toBeNull()
            ->and($insuredKyc->getRawOriginal('id_number'))->toBe($expectedEmiratesId);
    });
});
