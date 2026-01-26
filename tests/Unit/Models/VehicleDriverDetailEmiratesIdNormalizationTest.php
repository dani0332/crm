<?php

declare(strict_types=1);

use App\Models\CarQuote;
use App\Models\VehicleDriverDetail;
use Illuminate\Database\Eloquent\Model;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    // Temporarily disable mass assignment protection for unit tests
    // since we're testing the model's create() method behavior directly
    Model::unguard();
});

afterEach(function () {
    Model::reguard();
});

describe('VehicleDriverDetail Model - Driver Emirates ID Normalization', function () {
    test('create() removes hyphens from driver_eid_number', function () {
        $emiratesIdWithHyphens = '784-1985-1234567-1';
        $expectedEmiratesId = '784198512345671';

        $vehicleDriverDetail = VehicleDriverDetail::create([
            'quoteable_type' => CarQuote::class,
            'quoteable_id' => 1,
            'driver_eid_number' => $emiratesIdWithHyphens,
        ]);

        expect($vehicleDriverDetail)->not->toBeNull()
            ->and($vehicleDriverDetail->getRawOriginal('driver_eid_number'))->toBe($expectedEmiratesId);
    });

    test('create() handles null driver_eid_number gracefully', function () {
        $vehicleDriverDetail = VehicleDriverDetail::create([
            'quoteable_type' => CarQuote::class,
            'quoteable_id' => 2,
            'driver_eid_number' => null,
        ]);

        expect($vehicleDriverDetail)->not->toBeNull()
            ->and($vehicleDriverDetail->getRawOriginal('driver_eid_number'))->toBeNull();
    });

    test('accessor formats driver_eid_number as ###-####-#######-# when retrieving', function () {
        $emiratesIdWithoutHyphens = '784198512345671';
        $expectedFormatted = '784-1985-1234567-1';

        $vehicleDriverDetail = VehicleDriverDetail::create([
            'quoteable_type' => CarQuote::class,
            'quoteable_id' => 3,
            'driver_eid_number' => $emiratesIdWithoutHyphens,
        ]);

        expect($vehicleDriverDetail->driver_eid_number)->toBe($expectedFormatted);
    });

    test('accessor returns original value if not 15 digits', function () {
        $shortId = '12345';

        $vehicleDriverDetail = VehicleDriverDetail::create([
            'quoteable_type' => CarQuote::class,
            'quoteable_id' => 4,
            'driver_eid_number' => $shortId,
        ]);

        expect($vehicleDriverDetail->driver_eid_number)->toBe($shortId);
    });

    test('updateOrCreate() removes hyphens from driver_eid_number in attributes', function () {
        $emiratesIdWithHyphens = '784-1985-1234567-1';
        $expectedEmiratesId = '784198512345671';

        $vehicleDriverDetail = VehicleDriverDetail::updateOrCreate(
            [
                'quoteable_type' => CarQuote::class,
                'quoteable_id' => 5,
            ],
            [
                'driver_eid_number' => $emiratesIdWithHyphens,
                'driver_first_name' => 'John',
                'driver_last_name' => 'Doe',
            ]
        );

        expect($vehicleDriverDetail)->not->toBeNull()
            ->and($vehicleDriverDetail->getRawOriginal('driver_eid_number'))->toBe($expectedEmiratesId);
    });

    test('updateOrCreate() removes hyphens from driver_eid_number in values', function () {
        $emiratesIdWithHyphens = '784-1985-1234567-1';
        $expectedEmiratesId = '784198512345671';

        // Create initial record
        $vehicleDriverDetail = VehicleDriverDetail::create([
            'quoteable_type' => CarQuote::class,
            'quoteable_id' => 6,
            'driver_eid_number' => '784198512345671',
        ]);

        // Update with hyphens in values
        $updated = VehicleDriverDetail::updateOrCreate(
            [
                'id' => $vehicleDriverDetail->id,
            ],
            [
                'driver_eid_number' => $emiratesIdWithHyphens,
            ]
        );

        expect($updated)->not->toBeNull()
            ->and($updated->getRawOriginal('driver_eid_number'))->toBe($expectedEmiratesId);
    });

    test('updateOrCreate() removes hyphens from driver_eid_number in both attributes and values', function () {
        $emiratesIdWithHyphens = '784-1985-1234567-1';
        $expectedEmiratesId = '784198512345671';

        $vehicleDriverDetail = VehicleDriverDetail::updateOrCreate(
            [
                'quoteable_type' => CarQuote::class,
                'quoteable_id' => 7,
            ],
            [
                'driver_first_name' => 'John',
                'driver_last_name' => 'Doe',
                'driver_eid_number' => $emiratesIdWithHyphens,
            ]
        );

        expect($vehicleDriverDetail)->not->toBeNull()
            ->and($vehicleDriverDetail->getRawOriginal('driver_eid_number'))->toBe($expectedEmiratesId);
    });

    test('normalizes different hyphen patterns', function () {
        $testCases = [
            '784-1985-1234567-1',
            '784-1985-1234567-1-',
            '-784-1985-1234567-1',
            '784--1985--1234567--1',
        ];

        foreach ($testCases as $index => $emiratesIdWithHyphens) {
            $expectedEmiratesId = '784198512345671';

            $vehicleDriverDetail = VehicleDriverDetail::create([
                'quoteable_type' => CarQuote::class,
                'quoteable_id' => 8 + $index, // Different IDs for each test case
                'driver_eid_number' => $emiratesIdWithHyphens,
            ]);

            expect($vehicleDriverDetail->getRawOriginal('driver_eid_number'))
                ->toBe($expectedEmiratesId, "Failed to normalize: {$emiratesIdWithHyphens}");
        }
    });

    test('handles non-string driver_eid_number gracefully', function () {
        $vehicleDriverDetail = VehicleDriverDetail::create([
            'quoteable_type' => CarQuote::class,
            'quoteable_id' => 12,
            'driver_eid_number' => 123456789012345, // numeric value
        ]);

        expect($vehicleDriverDetail)->not->toBeNull()
            ->and($vehicleDriverDetail->getRawOriginal('driver_eid_number'))->not->toBeNull();
    });

    test('mutator removes hyphens when setting driver_eid_number', function () {
        $emiratesIdWithHyphens = '784-1985-1234567-1';
        $expectedEmiratesId = '784198512345671';

        $vehicleDriverDetail = new VehicleDriverDetail([
            'quoteable_type' => CarQuote::class,
            'quoteable_id' => 13,
            'driver_eid_number' => $emiratesIdWithHyphens,
        ]);

        expect($vehicleDriverDetail->getAttributes()['driver_eid_number'])->toBe($expectedEmiratesId);
    });

    test('accessor formats stored 15-digit ID with hyphens on retrieval', function () {
        $storedId = '784198512345671';
        $expectedFormatted = '784-1985-1234567-1';

        $vehicleDriverDetail = VehicleDriverDetail::create([
            'quoteable_type' => CarQuote::class,
            'quoteable_id' => 14,
            'driver_eid_number' => $storedId,
        ]);

        // Retrieve fresh from database to test accessor
        $refreshed = VehicleDriverDetail::find($vehicleDriverDetail->id);

        expect($refreshed->driver_eid_number)->toBe($expectedFormatted)
            ->and($refreshed->getRawOriginal('driver_eid_number'))->toBe($storedId);
    });

    test('accessor returns null when driver_eid_number is null', function () {
        $vehicleDriverDetail = VehicleDriverDetail::create([
            'quoteable_type' => CarQuote::class,
            'quoteable_id' => 15,
            'driver_eid_number' => null,
        ]);

        expect($vehicleDriverDetail->driver_eid_number)->toBeNull();
    });

    test('accessor returns original value when ID is less than 15 digits', function () {
        $shortId = '12345678';

        $vehicleDriverDetail = VehicleDriverDetail::create([
            'quoteable_type' => CarQuote::class,
            'quoteable_id' => 16,
            'driver_eid_number' => $shortId,
        ]);

        expect($vehicleDriverDetail->driver_eid_number)->toBe($shortId);
    });

    test('accessor returns original value when ID is more than 15 digits', function () {
        $longId = '7841985123456789';

        $vehicleDriverDetail = VehicleDriverDetail::create([
            'quoteable_type' => CarQuote::class,
            'quoteable_id' => 17,
            'driver_eid_number' => $longId,
        ]);

        expect($vehicleDriverDetail->driver_eid_number)->toBe($longId);
    });

    test('mutator handles null value gracefully', function () {
        $vehicleDriverDetail = new VehicleDriverDetail([
            'quoteable_type' => CarQuote::class,
            'quoteable_id' => 18,
            'driver_eid_number' => null,
        ]);

        expect($vehicleDriverDetail->getAttributes()['driver_eid_number'])->toBeNull();
    });

    test('mutator handles empty string', function () {
        $vehicleDriverDetail = new VehicleDriverDetail([
            'quoteable_type' => CarQuote::class,
            'quoteable_id' => 19,
            'driver_eid_number' => '',
        ]);

        expect($vehicleDriverDetail->getAttributes()['driver_eid_number'])->toBe('');
    });

    test('mutator removes multiple hyphens from various positions', function () {
        $testCases = [
            '784-1985-1234567-1' => '784198512345671',
            '-784-1985-1234567-1-' => '784198512345671',
            '784--1985--1234567--1' => '784198512345671',
            '---784---1985---1234567---1---' => '784198512345671',
        ];

        foreach ($testCases as $input => $expected) {
            $vehicleDriverDetail = new VehicleDriverDetail([
                'driver_eid_number' => $input,
            ]);

            expect($vehicleDriverDetail->getAttributes()['driver_eid_number'])
                ->toBe($expected, "Failed to normalize: {$input}");
        }
    });

    test('mutator preserves numeric values by converting to string', function () {
        $numericId = 784198512345671;
        $expected = '784198512345671';

        $vehicleDriverDetail = new VehicleDriverDetail([
            'quoteable_type' => CarQuote::class,
            'quoteable_id' => 20,
            'driver_eid_number' => $numericId,
        ]);

        expect($vehicleDriverDetail->getAttributes()['driver_eid_number'])->toBe($expected);
    });

    test('accessor and mutator work together correctly in round trip', function () {
        $inputWithHyphens = '784-1985-1234567-1';
        $storedWithoutHyphens = '784198512345671';
        $retrievedWithHyphens = '784-1985-1234567-1';

        // Create with hyphens
        $vehicleDriverDetail = VehicleDriverDetail::create([
            'quoteable_type' => CarQuote::class,
            'quoteable_id' => 21,
            'driver_eid_number' => $inputWithHyphens,
        ]);

        // Verify stored without hyphens
        expect($vehicleDriverDetail->getRawOriginal('driver_eid_number'))->toBe($storedWithoutHyphens);

        // Verify retrieved with hyphens
        expect($vehicleDriverDetail->driver_eid_number)->toBe($retrievedWithHyphens);

        // Refresh from database and verify again
        $refreshed = VehicleDriverDetail::find($vehicleDriverDetail->id);
        expect($refreshed->driver_eid_number)->toBe($retrievedWithHyphens)
            ->and($refreshed->getRawOriginal('driver_eid_number'))->toBe($storedWithoutHyphens);
    });

    test('accessor handles whitespace in stored ID', function () {
        $vehicleDriverDetail = new VehicleDriverDetail;
        $vehicleDriverDetail->setRawAttributes(['driver_eid_number' => ' 784198512345671 ']);

        // Accessor should return the value with spaces as-is since it's not exactly 15 digits after cleaning
        expect($vehicleDriverDetail->driver_eid_number)->toBe(' 784198512345671 ');
    });

    test('mutator only removes hyphens not other characters', function () {
        $idWithSpecialChars = '784.1985/1234567-1';
        $expected = '784.1985/12345671'; // Only hyphens removed

        $vehicleDriverDetail = new VehicleDriverDetail([
            'driver_eid_number' => $idWithSpecialChars,
        ]);

        expect($vehicleDriverDetail->getAttributes()['driver_eid_number'])->toBe($expected);
    });
});
