<?php

declare(strict_types=1);

use App\Enums\CarRegistrationType;
use App\Enums\CarVehicleUse;
use App\Enums\DocumentTypeCode;
use App\Models\CarQuote;
use App\Models\Nationality;
use App\Models\QuoteDocument;
use App\Models\VehicleDriverDetail;
use App\Services\OCR\EmiratesId\DriverEmiratesIdDataProcessor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

describe('Driver Emirates ID OCR Processing Flow', function () {
    test('processes OCR data and updates VehicleDriverDetail with driver_eid_number and gender', function () {
        // Create CarQuote
        $quote = CarQuote::create([
            'uuid' => 'test-quote-uuid-1',
            'code' => 'TEST-001',
            'registration_type' => CarRegistrationType::PERSONAL,
            'vehicle_use' => CarVehicleUse::PRIVATE,
        ]);

        // Prepare OCR data
        $ocrData = (object) [
            'idNumber' => '784-1985-1234567-1',
            'name' => 'John Doe Smith',
            'dateOfBirth' => '1985-05-15',
            'nationality' => 'United Arab Emirates',
            'sex' => 'M',
        ];

        // Process OCR data
        $processor = new DriverEmiratesIdDataProcessor($quote, $ocrData, DocumentTypeCode::DRIVER_EMIRATES_ID);
        $result = $processor->processDriverEmiratesIdData();

        expect($result)->toBeTrue();

        // Verify VehicleDriverDetail was created/updated
        $vehicleDriverDetail = VehicleDriverDetail::where('quoteable_id', $quote->id)
            ->where('quoteable_type', CarQuote::class)
            ->first();

        expect($vehicleDriverDetail)->not->toBeNull()
            ->and($vehicleDriverDetail->getRawOriginal('driver_eid_number'))->toBe('784198512345671')
            ->and($vehicleDriverDetail->driver_gender)->toBe('male');
    });

    test('processes OCR data and updates CarQuote for Company + Private combination', function () {
        // Create nationality (temporarily disable mass assignment protection)
        Model::unguard();
        $nationality = Nationality::create([
            'text' => 'United Arab Emirates',
            'code' => 'UAE',
        ]);
        Model::reguard();

        // Create CarQuote with Company registration and Private use
        $quote = CarQuote::create([
            'uuid' => 'test-quote-uuid-2',
            'code' => 'TEST-002',
            'registration_type' => CarRegistrationType::COMPANY,
            'vehicle_use' => CarVehicleUse::PRIVATE,
        ]);

        // Prepare OCR data
        $ocrData = (object) [
            'idNumber' => '784-1985-1234567-1',
            'name' => 'Jane Smith',
            'dateOfBirth' => '1990-08-20',
            'nationality' => 'United Arab Emirates',
            'sex' => 'F',
        ];

        // Process OCR data
        $processor = new DriverEmiratesIdDataProcessor($quote, $ocrData, DocumentTypeCode::DRIVER_EMIRATES_ID);
        $result = $processor->processDriverEmiratesIdData();

        expect($result)->toBeTrue();

        // Verify VehicleDriverDetail was created/updated
        $vehicleDriverDetail = VehicleDriverDetail::where('quoteable_id', $quote->id)
            ->where('quoteable_type', CarQuote::class)
            ->first();

        expect($vehicleDriverDetail)->not->toBeNull()
            ->and($vehicleDriverDetail->getRawOriginal('driver_eid_number'))->toBe('784198512345671')
            ->and($vehicleDriverDetail->driver_gender)->toBe('female');

        // Verify CarQuote was updated
        $quote->refresh();
        expect($quote->driver_name)->toBe('Jane Smith')
            ->and($quote->dob->format('Y-m-d'))->toBe('1990-08-20')
            ->and($quote->nationality_id)->toBe($nationality->id);
    });

    test('does not update CarQuote for non-Company-Private combinations', function () {
        // Create CarQuote with Company registration and Company use
        $quote = CarQuote::create([
            'uuid' => 'test-quote-uuid-3',
            'code' => 'TEST-003',
            'registration_type' => CarRegistrationType::COMPANY,
            'vehicle_use' => CarVehicleUse::COMMERCIAL,
        ]);

        // Prepare OCR data
        $ocrData = (object) [
            'idNumber' => '784-1985-1234567-1',
            'name' => 'Bob Johnson',
            'dateOfBirth' => '1988-03-10',
            'nationality' => 'United Arab Emirates',
            'sex' => 'M',
        ];

        // Process OCR data
        $processor = new DriverEmiratesIdDataProcessor($quote, $ocrData, DocumentTypeCode::DRIVER_EMIRATES_ID);
        $result = $processor->processDriverEmiratesIdData();

        expect($result)->toBeTrue();

        // Verify VehicleDriverDetail was created
        $vehicleDriverDetail = VehicleDriverDetail::where('quoteable_id', $quote->id)
            ->where('quoteable_type', CarQuote::class)
            ->first();

        expect($vehicleDriverDetail)->not->toBeNull();

        // Verify CarQuote was NOT updated
        $quote->refresh();
        expect($quote->driver_name)->toBeNull()
            ->and($quote->dob)->toBeNull()
            ->and($quote->nationality_id)->toBeNull();
    });

    test('handles transaction rollback on failure', function () {
        // Create a valid CarQuote
        $quote = CarQuote::create([
            'uuid' => 'test-rollback-uuid',
            'code' => 'ROLLBACK-001',
            'registration_type' => CarRegistrationType::PERSONAL,
            'vehicle_use' => CarVehicleUse::PRIVATE,
        ]);

        // Prepare empty OCR data which should result in false
        $ocrData = (object) [];

        $processor = new DriverEmiratesIdDataProcessor($quote, $ocrData, DocumentTypeCode::DRIVER_EMIRATES_ID);
        $result = $processor->processDriverEmiratesIdData();

        // Should return false for empty data
        expect($result)->toBeFalse();
    });

    test('returns false when no data to update', function () {
        // Create CarQuote
        $quote = CarQuote::create([
            'uuid' => 'test-quote-uuid-4',
            'code' => 'TEST-004',
            'registration_type' => CarRegistrationType::PERSONAL,
            'vehicle_use' => CarVehicleUse::PRIVATE,
        ]);

        // Prepare OCR data with all null/empty values
        $ocrData = (object) [
            'idNumber' => null,
            'sex' => null,
        ];

        // Process OCR data
        $processor = new DriverEmiratesIdDataProcessor($quote, $ocrData, DocumentTypeCode::DRIVER_EMIRATES_ID);
        $result = $processor->processDriverEmiratesIdData();

        expect($result)->toBeFalse();
    });

    test('updates existing VehicleDriverDetail if already exists', function () {
        // Create CarQuote
        $quote = CarQuote::create([
            'uuid' => 'test-quote-uuid-5',
            'code' => 'TEST-005',
            'registration_type' => CarRegistrationType::PERSONAL,
            'vehicle_use' => CarVehicleUse::PRIVATE,
        ]);

        // Create existing VehicleDriverDetail via relationship
        $existingDetail = $quote->vehicleDriverDetail()->create([
            'driver_first_name' => 'Existing',
            'driver_last_name' => 'Driver',
        ]);

        // Prepare OCR data
        $ocrData = (object) [
            'idNumber' => '784-1985-1234567-1',
            'sex' => 'F',
        ];

        // Process OCR data
        $processor = new DriverEmiratesIdDataProcessor($quote, $ocrData, DocumentTypeCode::DRIVER_EMIRATES_ID);
        $result = $processor->processDriverEmiratesIdData();

        expect($result)->toBeTrue();

        // Verify the same record was updated (not a new one created)
        $count = VehicleDriverDetail::where('quoteable_id', $quote->id)
            ->where('quoteable_type', CarQuote::class)
            ->count();

        expect($count)->toBe(1);

        // Verify data was updated
        $vehicleDriverDetail = VehicleDriverDetail::where('quoteable_id', $quote->id)
            ->where('quoteable_type', CarQuote::class)
            ->first();

        expect($vehicleDriverDetail->id)->toBe($existingDetail->id)
            ->and($vehicleDriverDetail->driver_eid_number)->not->toBeNull()
            ->and($vehicleDriverDetail->driver_gender)->toBe('female')
            ->and($vehicleDriverDetail->driver_first_name)->toBe('Existing')
            ->and($vehicleDriverDetail->driver_last_name)->toBe('Driver');
    });

    test('generates processing summary correctly', function () {
        // Create nationality (temporarily disable mass assignment protection)
        Model::unguard();
        $nationality = Nationality::create([
            'text' => 'United Arab Emirates',
            'code' => 'UAE',
        ]);
        Model::reguard();

        // Create CarQuote
        $quote = CarQuote::create([
            'uuid' => 'test-quote-uuid-6',
            'code' => 'TEST-006',
            'registration_type' => CarRegistrationType::COMPANY,
            'vehicle_use' => CarVehicleUse::PRIVATE,
        ]);

        // Prepare OCR data
        $ocrData = (object) [
            'idNumber' => '784-1985-1234567-1',
            'name' => 'John Doe',
            'dateOfBirth' => '1985-05-15',
            'nationality' => 'United Arab Emirates',
            'sex' => 'M',
        ];

        // Process OCR data
        $processor = new DriverEmiratesIdDataProcessor($quote, $ocrData, DocumentTypeCode::DRIVER_EMIRATES_ID);
        $processor->processDriverEmiratesIdData();

        // Get processing summary
        $summary = $processor->getProcessingSummary();

        expect($summary)->toBeArray()
            ->and($summary['status'])->toBe('success')
            ->and($summary['quote_uuid'])->toBe('test-quote-uuid-6')
            ->and($summary['document_type_code'])->toBe(DocumentTypeCode::DRIVER_EMIRATES_ID)
            ->and($summary['car_quote_data'])->toBeArray()
            ->and($summary['car_quote_data']['driver_name'])->toBe('John Doe')
            ->and($summary['car_quote_data']['dob']->format('Y-m-d'))->toBe('1985-05-15')
            ->and($summary['car_quote_data']['nationality_id'])->toBe($nationality->id)
            ->and($summary['vehicle_driver_detail_data'])->toBeArray()
            ->and($summary['vehicle_driver_detail_data']['driver_eid_number'])->toBe('784-1985-1234567-1');
    });

    test('validates and updates QuoteDocument OCR flag on success', function () {
        // Create CarQuote
        $quote = CarQuote::create([
            'uuid' => 'test-quote-uuid-7',
            'code' => 'TEST-007',
            'registration_type' => CarRegistrationType::PERSONAL,
            'vehicle_use' => CarVehicleUse::PRIVATE,
        ]);

        // Create QuoteDocument via relationship
        $quote->documents()->create([
            'document_type_code' => DocumentTypeCode::DRIVER_EMIRATES_ID,
            'is_ocr_processed' => false,
        ]);

        // Prepare OCR data
        $ocrData = (object) [
            'idNumber' => '784-1985-1234567-1',
            'sex' => 'M',
        ];

        // Process OCR data
        $processor = new DriverEmiratesIdDataProcessor($quote, $ocrData, DocumentTypeCode::DRIVER_EMIRATES_ID);
        $processor->processDriverEmiratesIdData();

        // Verify QuoteDocument was updated
        $quoteDocument = QuoteDocument::where('quote_documentable_id', $quote->id)
            ->where('document_type_code', DocumentTypeCode::DRIVER_EMIRATES_ID)
            ->first();

        expect($quoteDocument->is_ocr_processed)->toBe(1);
    });

    test('handles partial data extraction', function () {
        // Create CarQuote
        $quote = CarQuote::create([
            'uuid' => 'test-quote-uuid-8',
            'code' => 'TEST-008',
            'registration_type' => CarRegistrationType::PERSONAL,
            'vehicle_use' => CarVehicleUse::PRIVATE,
        ]);

        // Prepare OCR data with only some fields
        $ocrData = (object) [
            'idNumber' => '784-1985-1234567-1',
            // sex is missing
        ];

        // Process OCR data
        $processor = new DriverEmiratesIdDataProcessor($quote, $ocrData, DocumentTypeCode::DRIVER_EMIRATES_ID);
        $result = $processor->processDriverEmiratesIdData();

        expect($result)->toBeTrue();

        // Verify VehicleDriverDetail was created with available data
        $vehicleDriverDetail = VehicleDriverDetail::where('quoteable_id', $quote->id)
            ->where('quoteable_type', CarQuote::class)
            ->first();

        expect($vehicleDriverDetail)->not->toBeNull()
            ->and($vehicleDriverDetail->driver_eid_number)->not->toBeNull()
            ->and($vehicleDriverDetail->driver_gender)->toBeNull();
    });
});
