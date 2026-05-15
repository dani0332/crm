<?php

declare(strict_types=1);

use App\Enums\CarRegistrationType;
use App\Enums\CarVehicleUse;
use App\Enums\QuoteTypeId;
use App\Models\DocumentType;
use App\Services\QuoteDocumentService;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToCheckExistence;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->service = app(QuoteDocumentService::class);
});

describe('QuoteDocumentService - Document Type Filtering', function () {
    test('getQuoteDocumentsToReceive returns documents where registration_type is null or matches', function () {
        // Create document types
        DocumentType::factory()->create([
            'code' => 'DOC_NULL',
            'text' => 'Document with null registration',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => null,
            'vehicle_use' => null,
            'sort_order' => 1,
        ]);

        DocumentType::factory()->create([
            'code' => 'DOC_COMPANY',
            'text' => 'Document for Company',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => CarRegistrationType::COMPANY,
            'vehicle_use' => null,
            'sort_order' => 2,
        ]);

        DocumentType::factory()->create([
            'code' => 'DOC_PERSONAL',
            'text' => 'Document for Personal',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => CarRegistrationType::PERSONAL,
            'vehicle_use' => null,
            'sort_order' => 3,
        ]);

        // Call service with COMPANY registration_type
        $documents = $this->service->getQuoteDocumentsToReceive(
            QuoteTypeId::CompanyCar,
            CarRegistrationType::COMPANY,
            null
        );

        $codes = $documents->pluck('code')->toArray();
        expect($codes)->toContain('DOC_NULL')
            ->and($codes)->toContain('DOC_COMPANY')
            ->and($codes)->not->toContain('DOC_PERSONAL');
    });

    test('getQuoteDocumentsToReceive returns documents where vehicle_use is null or matches', function () {
        // Create document types
        DocumentType::factory()->create([
            'code' => 'DOC_NULL',
            'text' => 'Document with null vehicle use',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => null,
            'vehicle_use' => null,
            'sort_order' => 1,
        ]);

        DocumentType::factory()->create([
            'code' => 'DOC_PRIVATE',
            'text' => 'Document for Private',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => null,
            'vehicle_use' => CarVehicleUse::PRIVATE,
            'sort_order' => 2,
        ]);

        DocumentType::factory()->create([
            'code' => 'DOC_COMPANY',
            'text' => 'Document for Company',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => null,
            'vehicle_use' => CarVehicleUse::COMMERCIAL,
            'sort_order' => 3,
        ]);

        // Call service with PRIVATE vehicle_use
        $documents = $this->service->getQuoteDocumentsToReceive(
            QuoteTypeId::CompanyCar,
            null,
            CarVehicleUse::PRIVATE
        );

        $codes = $documents->pluck('code')->toArray();
        expect($codes)->toContain('DOC_NULL')
            ->and($codes)->toContain('DOC_PRIVATE')
            ->and($codes)->not->toContain('DOC_COMPANY');
    });

    test('getQuoteDocumentsToReceive applies both filters correctly', function () {
        // Create document types with various combinations
        DocumentType::factory()->create([
            'code' => 'DOC_NULL_NULL',
            'text' => 'Document null/null',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => null,
            'vehicle_use' => null,
            'sort_order' => 1,
        ]);

        DocumentType::factory()->create([
            'code' => 'DOC_COMPANY_PRIVATE',
            'text' => 'Document Company/Private',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => CarRegistrationType::COMPANY,
            'vehicle_use' => CarVehicleUse::PRIVATE,
            'sort_order' => 2,
        ]);

        DocumentType::factory()->create([
            'code' => 'DOC_COMPANY_NULL',
            'text' => 'Document Company/null',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => CarRegistrationType::COMPANY,
            'vehicle_use' => null,
            'sort_order' => 3,
        ]);

        DocumentType::factory()->create([
            'code' => 'DOC_NULL_PRIVATE',
            'text' => 'Document null/Private',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => null,
            'vehicle_use' => CarVehicleUse::PRIVATE,
            'sort_order' => 4,
        ]);

        DocumentType::factory()->create([
            'code' => 'DOC_PERSONAL_PRIVATE',
            'text' => 'Document Personal/Private',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => CarRegistrationType::PERSONAL,
            'vehicle_use' => CarVehicleUse::PRIVATE,
            'sort_order' => 5,
        ]);

        DocumentType::factory()->create([
            'code' => 'DOC_COMPANY_COMPANY',
            'text' => 'Document Company/Company',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => CarRegistrationType::COMPANY,
            'vehicle_use' => CarVehicleUse::COMMERCIAL,
            'sort_order' => 6,
        ]);

        // Call service with COMPANY registration_type and PRIVATE vehicle_use
        $documents = $this->service->getQuoteDocumentsToReceive(
            QuoteTypeId::CompanyCar,
            CarRegistrationType::COMPANY,
            CarVehicleUse::PRIVATE
        );

        $codes = $documents->pluck('code')->toArray();

        // Should include: null/null, Company/Private, Company/null, null/Private
        expect($codes)->toContain('DOC_NULL_NULL')
            ->and($codes)->toContain('DOC_COMPANY_PRIVATE')
            ->and($codes)->toContain('DOC_COMPANY_NULL')
            ->and($codes)->toContain('DOC_NULL_PRIVATE')
            ->and($codes)->not->toContain('DOC_PERSONAL_PRIVATE')
            ->and($codes)->not->toContain('DOC_COMPANY_COMPANY');
    });

    test('getQuoteDocumentsToReceive filtering only applies to CompanyCar quote type', function () {
        // Create documents for different quote types
        DocumentType::factory()->create([
            'code' => 'DOC_PERSONAL_CAR',
            'text' => 'Personal Car Document',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::Car,
            'registration_type' => CarRegistrationType::PERSONAL,
            'vehicle_use' => null,
            'sort_order' => 1,
        ]);

        DocumentType::factory()->create([
            'code' => 'DOC_PERSONAL_CAR_2',
            'text' => 'Personal Car Document 2',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::Car,
            'registration_type' => CarRegistrationType::COMPANY,
            'vehicle_use' => null,
            'sort_order' => 2,
        ]);

        // Call service for Personal Car (non-CompanyCar) with filters
        // Filters should be ignored for non-CompanyCar types
        $documents = $this->service->getQuoteDocumentsToReceive(
            QuoteTypeId::Car,
            CarRegistrationType::COMPANY,
            CarVehicleUse::PRIVATE
        );

        // Should return all documents regardless of registration_type/vehicle_use
        $codes = $documents->pluck('code')->toArray();
        expect($codes)->toContain('DOC_PERSONAL_CAR')
            ->and($codes)->toContain('DOC_PERSONAL_CAR_2');
    });

    test('getQuoteDocumentsToReceive returns only active documents', function () {
        // Create active and inactive documents
        DocumentType::factory()->create([
            'code' => 'DOC_ACTIVE',
            'text' => 'Active Document',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => null,
            'vehicle_use' => null,
            'sort_order' => 1,
        ]);

        DocumentType::factory()->create([
            'code' => 'DOC_INACTIVE',
            'text' => 'Inactive Document',
            'is_active' => 0,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => null,
            'vehicle_use' => null,
            'sort_order' => 2,
        ]);

        $documents = $this->service->getQuoteDocumentsToReceive(
            QuoteTypeId::CompanyCar,
            null,
            null
        );

        $codes = $documents->pluck('code')->toArray();
        expect($codes)->toContain('DOC_ACTIVE')
            ->and($codes)->not->toContain('DOC_INACTIVE');
    });

    test('getQuoteDocumentsToReceive returns only documents that receive from customer', function () {
        // Create documents with different receive_from_customer values
        DocumentType::factory()->create([
            'code' => 'DOC_RECEIVE',
            'text' => 'Receive Document',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => null,
            'vehicle_use' => null,
            'sort_order' => 1,
        ]);

        DocumentType::factory()->create([
            'code' => 'DOC_NOT_RECEIVE',
            'text' => 'Not Receive Document',
            'is_active' => 1,
            'receive_from_customer' => 0,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => null,
            'vehicle_use' => null,
            'sort_order' => 2,
        ]);

        $documents = $this->service->getQuoteDocumentsToReceive(
            QuoteTypeId::CompanyCar,
            null,
            null
        );

        $codes = $documents->pluck('code')->toArray();
        expect($codes)->toContain('DOC_RECEIVE')
            ->and($codes)->not->toContain('DOC_NOT_RECEIVE');
    });

    test('getQuoteDocumentsToReceive excludes restricted internal document types', function () {
        DocumentType::factory()->create([
            'code' => 'DOC_PUBLIC_RECEIVE',
            'text' => 'Public receive document',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => null,
            'vehicle_use' => null,
            'sort_order' => 1,
            'is_restricted_internal_document' => 0,
        ]);

        DocumentType::factory()->create([
            'code' => 'DOC_RESTRICTED_RECEIVE',
            'text' => 'Restricted internal document',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => null,
            'vehicle_use' => null,
            'sort_order' => 2,
            'is_restricted_internal_document' => 1,
        ]);

        $documents = $this->service->getQuoteDocumentsToReceive(
            QuoteTypeId::CompanyCar,
            null,
            null
        );

        $codes = $documents->pluck('code')->toArray();
        expect($codes)->toContain('DOC_PUBLIC_RECEIVE')
            ->and($codes)->not->toContain('DOC_RESTRICTED_RECEIVE');
    });

    test('getQuoteDocumentsToReceive returns documents sorted by sort_order', function () {
        // Create documents with different sort orders
        DocumentType::factory()->create([
            'code' => 'DOC_THIRD',
            'text' => 'Third',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => null,
            'vehicle_use' => null,
            'sort_order' => 30,
        ]);

        DocumentType::factory()->create([
            'code' => 'DOC_FIRST',
            'text' => 'First',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => null,
            'vehicle_use' => null,
            'sort_order' => 10,
        ]);

        DocumentType::factory()->create([
            'code' => 'DOC_SECOND',
            'text' => 'Second',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => null,
            'vehicle_use' => null,
            'sort_order' => 20,
        ]);

        $documents = $this->service->getQuoteDocumentsToReceive(
            QuoteTypeId::CompanyCar,
            null,
            null
        );

        $codes = $documents->pluck('code')->toArray();
        expect($codes[0])->toBe('DOC_FIRST')
            ->and($codes[1])->toBe('DOC_SECOND')
            ->and($codes[2])->toBe('DOC_THIRD');
    });

    test('getQuoteDocumentsToReceive works without filters', function () {
        // Create documents - for CompanyCar, only documents with null fields will be returned when no filters provided
        DocumentType::factory()->create([
            'code' => 'DOC1',
            'text' => 'Document 1',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => null,
            'vehicle_use' => null,
            'sort_order' => 1,
        ]);

        DocumentType::factory()->create([
            'code' => 'DOC2',
            'text' => 'Document 2',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => null, // Changed to null to match filtering behavior
            'vehicle_use' => null, // Changed to null to match filtering behavior
            'sort_order' => 2,
        ]);

        // Call without filters - for CompanyCar, this returns documents where both fields are null
        $documents = $this->service->getQuoteDocumentsToReceive(QuoteTypeId::CompanyCar);

        expect($documents->count())->toBe(2);
    });

    test('getQuoteDocumentsToReceive returns empty collection when no documents match', function () {
        // Create document with specific registration_type
        DocumentType::factory()->create([
            'code' => 'DOC_PERSONAL',
            'text' => 'Personal Document',
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => CarRegistrationType::PERSONAL,
            'vehicle_use' => null,
            'sort_order' => 1,
        ]);

        // Request with different registration_type
        $documents = $this->service->getQuoteDocumentsToReceive(
            QuoteTypeId::CompanyCar,
            CarRegistrationType::COMPANY,
            null
        );

        expect($documents->count())->toBe(0);
    });
});

describe('getDocumentUrl', function () {
    test('returns null when Azure existence check fails after retries without throwing', function () {
        $path = 'documents/car/transient.pdf';
        $storageDisk = Mockery::mock();
        $storageDisk->shouldReceive('exists')
            ->times(3)
            ->andThrow(UnableToCheckExistence::forLocation(
                $path,
                new RuntimeException('cURL error 6: Could not resolve host')
            ));

        Storage::shouldReceive('disk')
            ->with('azureIMPrivate')
            ->times(3)
            ->andReturn($storageDisk);

        expect($this->service->getDocumentUrl($path))->toBeNull();
    });

    test('returns temporary URL when file exists', function () {
        $path = 'documents/car/ok.pdf';
        $storageDisk = Mockery::mock();
        $storageDisk->shouldReceive('exists')->once()->andReturn(true);
        $storageDisk->shouldReceive('temporaryUrl')->once()->andReturn('https://example.com/signed');

        Storage::shouldReceive('disk')
            ->with('azureIMPrivate')
            ->twice()
            ->andReturn($storageDisk);

        expect($this->service->getDocumentUrl($path))->toBe('https://example.com/signed');
    });
});
