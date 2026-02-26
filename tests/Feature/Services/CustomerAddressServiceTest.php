<?php

declare(strict_types=1);

use App\Enums\QuoteTypes;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Services\CustomerAddressService;
use Tests\Helpers\TestSchemaCreator;
use Tests\Support\Schema\SchemaUtils;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    SchemaUtils::ensureTable('customer_addresses', function ($table) {
        $table->id();
        $table->unsignedBigInteger('customer_id');
        $table->string('type')->nullable();
        $table->unsignedBigInteger('quote_type_id');
        $table->string('quote_uuid');
        $table->string('office_number')->nullable();
        $table->string('floor_number')->nullable();
        $table->string('building_name')->nullable();
        $table->string('street')->nullable();
        $table->string('area')->nullable();
        $table->string('city')->nullable();
        $table->string('landmark')->nullable();
        $table->boolean('is_default')->default(0);
        $table->boolean('is_courier_address')->default(0);
        $table->timestamps();
    });

    $this->service = app(CustomerAddressService::class);
});

describe('CustomerAddressService - Optional Fields Handling', function () {
    test('createOrUpdateCustomerAddress handles missing optional fields street_name and landmark', function () {
        $customer = Customer::factory()->create();

        $addressData = [
            'address_type' => 'Home',
            'villa_apartment_office_no' => '101',
            'floor_no' => '1',
            'villa_building_name' => 'Test Building',
            'area' => 'Test Area',
            'city' => 'Dubai',
        ];

        $this->service->createOrUpdateCustomerAddress(
            $addressData,
            $customer->id,
            'test-uuid-123',
            QuoteTypes::CYBER->id()
        );

        $address = CustomerAddress::where('quote_uuid', 'test-uuid-123')->first();

        expect($address)->not->toBeNull()
            ->and($address->customer_id)->toBe($customer->id)
            ->and($address->type)->toBe('Home')
            ->and($address->office_number)->toBe('101')
            ->and($address->floor_number)->toBe('1')
            ->and($address->building_name)->toBe('Test Building')
            ->and($address->street)->toBeNull()
            ->and($address->area)->toBe('Test Area')
            ->and($address->city)->toBe('Dubai')
            ->and($address->landmark)->toBeNull()
            ->and($address->is_default)->toBe(1);
    });

    test('createOrUpdateCustomerAddress handles present optional fields street_name and landmark', function () {
        $customer = Customer::factory()->create();

        $addressData = [
            'address_type' => 'Office',
            'villa_apartment_office_no' => '202',
            'floor_no' => '2',
            'villa_building_name' => 'Office Tower',
            'street_name' => 'Sheikh Zayed Road',
            'area' => 'Downtown',
            'city' => 'Dubai',
            'landmark' => 'Near Metro Station',
        ];

        $this->service->createOrUpdateCustomerAddress(
            $addressData,
            $customer->id,
            'test-uuid-456',
            QuoteTypes::CAR->id()
        );

        $address = CustomerAddress::where('quote_uuid', 'test-uuid-456')->first();

        expect($address)->not->toBeNull()
            ->and($address->customer_id)->toBe($customer->id)
            ->and($address->type)->toBe('Office')
            ->and($address->street)->toBe('Sheikh Zayed Road')
            ->and($address->landmark)->toBe('Near Metro Station')
            ->and($address->is_default)->toBe(0);
    });

    test('createOrUpdateCustomerAddress updates existing address with missing optional fields', function () {
        $customer = Customer::factory()->create();

        CustomerAddress::create([
            'customer_id' => $customer->id,
            'quote_uuid' => 'test-uuid-789',
            'type' => 'Home',
            'quote_type_id' => QuoteTypes::CAR->id(),
            'office_number' => '301',
            'floor_number' => '3',
            'building_name' => 'Old Building',
            'street' => 'Old Street',
            'area' => 'Old Area',
            'city' => 'Old City',
            'landmark' => 'Old Landmark',
            'is_default' => 1,
        ]);

        $updatedAddressData = [
            'address_type' => 'Home',
            'villa_apartment_office_no' => '301',
            'floor_no' => '3',
            'villa_building_name' => 'Updated Building',
            'area' => 'Updated Area',
            'city' => 'Updated City',
        ];

        $this->service->createOrUpdateCustomerAddress(
            $updatedAddressData,
            $customer->id,
            'test-uuid-789',
            QuoteTypes::CYBER->id()
        );

        $address = CustomerAddress::where('quote_uuid', 'test-uuid-789')->first();

        expect($address)->not->toBeNull()
            ->and($address->building_name)->toBe('Updated Building')
            ->and($address->area)->toBe('Updated Area')
            ->and($address->city)->toBe('Updated City')
            ->and($address->street)->toBeNull()
            ->and($address->landmark)->toBeNull();
    });
});
