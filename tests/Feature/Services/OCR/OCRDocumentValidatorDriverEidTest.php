<?php

declare(strict_types=1);

use App\Enums\CarRegistrationType;
use App\Enums\CarVehicleUse;
use App\Models\CarQuote;
use App\Services\OCR\Validators\OCRDocumentValidator;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

describe('OCRDocumentValidator - Driver EID Validation', function () {
    test('validateDriverEidFields returns true when all required fields are present', function () {
        $quote = CarQuote::factory()->create([
            'uuid' => 'test-uuid-1',
            'code' => 'TEST-001',
            'registration_type' => CarRegistrationType::PERSONAL,
            'vehicle_use' => CarVehicleUse::PRIVATE,
        ]);

        $quote->vehicleDriverDetail()->create([
            'driver_eid_number' => '784198512345671',
        ]);

        $validator = new OCRDocumentValidator($quote->id, CarQuote::class);
        $result = $validator->validateDriverEidFields($quote);

        expect($result)->toBeTrue();
    });

    test('validateDriverEidFields returns false when driver_eid_number is missing', function () {
        $quote = CarQuote::factory()->create([
            'uuid' => 'test-uuid-2',
            'code' => 'TEST-002',
            'registration_type' => CarRegistrationType::PERSONAL,
            'vehicle_use' => CarVehicleUse::PRIVATE,
        ]);

        $quote->vehicleDriverDetail()->create([
            'driver_first_name' => 'John',
        ]);

        $validator = new OCRDocumentValidator($quote->id, CarQuote::class);
        $result = $validator->validateDriverEidFields($quote);

        expect($result)->toBeFalse();
    });

    test('validateDriverEidFields returns false when VehicleDriverDetail does not exist', function () {
        $quote = CarQuote::factory()->create([
            'uuid' => 'test-uuid-3',
            'code' => 'TEST-003',
            'registration_type' => CarRegistrationType::PERSONAL,
            'vehicle_use' => CarVehicleUse::PRIVATE,
        ]);

        $validator = new OCRDocumentValidator($quote->id, CarQuote::class);
        $result = $validator->validateDriverEidFields($quote);

        expect($result)->toBeFalse();
    });

    test('validateDriverEidFields validates CarQuote fields for Company + Private combination', function () {
        $quote = CarQuote::factory()->create([
            'uuid' => 'test-uuid-4',
            'code' => 'TEST-004',
            'registration_type' => CarRegistrationType::COMPANY,
            'vehicle_use' => CarVehicleUse::PRIVATE,
            'driver_name' => 'John Doe',
            'dob' => '1985-05-15',
            'nationality_id' => 1,
        ]);

        $quote->vehicleDriverDetail()->create([
            'driver_eid_number' => '784198512345671',
        ]);

        $validator = new OCRDocumentValidator($quote->id, CarQuote::class);
        $result = $validator->validateDriverEidFields($quote);

        expect($result)->toBeTrue();
    });

    test('validateDriverEidFields returns false when CarQuote fields missing for Company + Private', function () {
        $quote = CarQuote::factory()->create([
            'uuid' => 'test-uuid-5',
            'code' => 'TEST-005',
            'registration_type' => CarRegistrationType::COMPANY,
            'vehicle_use' => CarVehicleUse::PRIVATE,
            'driver_name' => null,
            'dob' => '1985-05-15',
            'nationality_id' => 1,
        ]);

        $quote->vehicleDriverDetail()->create([
            'driver_eid_number' => '784198512345671',
        ]);

        $validator = new OCRDocumentValidator($quote->id, CarQuote::class);
        $result = $validator->validateDriverEidFields($quote);

        expect($result)->toBeFalse();
    });

    test('validateDriverEidFields does not check CarQuote fields for non-Company+Private combinations', function () {
        $quote = CarQuote::factory()->create([
            'uuid' => 'test-uuid-6',
            'code' => 'TEST-006',
            'registration_type' => CarRegistrationType::PERSONAL,
            'vehicle_use' => CarVehicleUse::PRIVATE,
        ]);

        $quote->vehicleDriverDetail()->create([
            'driver_eid_number' => '784198512345671',
        ]);

        $validator = new OCRDocumentValidator($quote->id, CarQuote::class);
        $result = $validator->validateDriverEidFields($quote);

        expect($result)->toBeTrue();
    });
});
