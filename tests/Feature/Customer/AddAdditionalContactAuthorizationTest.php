<?php

declare(strict_types=1);

use App\Enums\GenericRequestEnum;
use App\Enums\RolesEnum;
use App\Models\CarQuote;
use App\Models\Customer;
use App\Models\CustomerAdditionalContact;
use App\Models\HealthQuote;
use App\Models\TravelQuote;
use App\Services\SLA\SLAService;
use Illuminate\Support\Str;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function (): void {
    TestSchemaCreator::createMinimalSchema();

    $this->instance(
        SLAService::class,
        Mockery::mock(SLAService::class)
            ->shouldReceive('meetSLAOnEdit')
            ->zeroOrMoreTimes()
            ->andReturnNull()
            ->getMock()
    );
});

afterEach(function (): void {
    Mockery::close();
});

function createTestCustomer(): Customer
{
    return Customer::query()->create([
        'first_name' => 'Test',
        'last_name' => 'Customer',
        'email' => 'cust-'.Str::uuid().'@example.com',
        'mobile_no' => '500123456',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('add additional contact returns unauthorized when user cannot access quote for car, health, and travel', function (string $quoteType) {
    $intruder = TestDataSeeder::createUser(['email' => 'intruder-'.Str::uuid().'@example.com']);
    $advisor = TestDataSeeder::createUserWithRole(
        match ($quoteType) {
            'car' => RolesEnum::CarAdvisor,
            'health' => RolesEnum::HealthAdvisor,
            'travel' => RolesEnum::TravelAdvisor,
            default => RolesEnum::CarAdvisor,
        },
        ['email' => 'advisor-'.Str::uuid().'@example.com']
    );

    $customer = createTestCustomer();

    $quote = match ($quoteType) {
        'car' => CarQuote::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'CAR-AC-'.Str::random(6),
            'advisor_id' => $advisor->id,
            'customer_id' => $customer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'health' => HealthQuote::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'HLT-AC-'.Str::random(6),
            'advisor_id' => $advisor->id,
            'customer_id' => $customer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'travel' => TravelQuote::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'TRV-AC-'.Str::random(6),
            'advisor_id' => $advisor->id,
            'customer_id' => $customer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        default => throw new InvalidArgumentException($quoteType),
    };

    $this->actingAs($intruder);

    $response = $this->postJson('/customer-additional-contact/add', [
        'customer_id' => $customer->id,
        'additional_contact_type' => GenericRequestEnum::MOBILE_NO,
        'additional_contact_val' => '+97150'.random_int(1000000, 9999999),
        'quote_type' => $quoteType,
        'quote_id' => $quote->id,
    ]);

    $response->assertOk()
        ->assertJsonPath('error.message', 'You are not authorized to add additional contact for this quote.');

    expect(CustomerAdditionalContact::query()->where('customer_id', $customer->id)->count())->toBe(0);
})->with(['car', 'health', 'travel']);

test('add additional contact returns inertia validation error when unauthorized user posts with isInertia', function (): void {
    $intruder = TestDataSeeder::createUser(['email' => 'inertia-intruder-'.Str::uuid().'@example.com']);
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor, ['email' => 'inertia-advisor-'.Str::uuid().'@example.com']);
    $customer = createTestCustomer();
    $quote = CarQuote::query()->create([
        'uuid' => (string) Str::uuid(),
        'code' => 'CAR-INERTIA-'.Str::random(6),
        'advisor_id' => $advisor->id,
        'customer_id' => $customer->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($intruder);

    $response = $this->from('/quotes/car/test')
        ->post('/customer-additional-contact/add', [
            'customer_id' => $customer->id,
            'additional_contact_type' => GenericRequestEnum::MOBILE_NO,
            'additional_contact_val' => '+97150'.random_int(1000000, 9999999),
            'quote_type' => 'car',
            'quote_id' => $quote->id,
            'isInertia' => true,
        ]);

    $response->assertSessionHasErrors('error');
    expect(CustomerAdditionalContact::query()->where('customer_id', $customer->id)->count())->toBe(0);
});

test('add additional contact succeeds when assigned advisor accesses car, health, and travel quotes', function (string $quoteType) {
    $role = match ($quoteType) {
        'car' => RolesEnum::CarAdvisor,
        'health' => RolesEnum::HealthAdvisor,
        'travel' => RolesEnum::TravelAdvisor,
        default => RolesEnum::CarAdvisor,
    };

    $advisor = TestDataSeeder::createUserWithRole($role, ['email' => 'advisor-ok-'.Str::uuid().'@example.com']);
    $customer = createTestCustomer();

    $quote = match ($quoteType) {
        'car' => CarQuote::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'CAR-OK-'.Str::random(6),
            'advisor_id' => $advisor->id,
            'customer_id' => $customer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'health' => HealthQuote::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'HLT-OK-'.Str::random(6),
            'advisor_id' => $advisor->id,
            'customer_id' => $customer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'travel' => TravelQuote::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'TRV-OK-'.Str::random(6),
            'advisor_id' => $advisor->id,
            'customer_id' => $customer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        default => throw new InvalidArgumentException($quoteType),
    };

    $mobile = '+97150'.random_int(1000000, 9999999);

    $this->actingAs($advisor);

    $response = $this->postJson('/customer-additional-contact/add', [
        'customer_id' => $customer->id,
        'additional_contact_type' => GenericRequestEnum::MOBILE_NO,
        'additional_contact_val' => $mobile,
        'quote_type' => $quoteType,
        'quote_id' => $quote->id,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.message', 'Contact added successfully.');

    expect(CustomerAdditionalContact::query()->where([
        'customer_id' => $customer->id,
        'key' => GenericRequestEnum::MOBILE_NO,
        'value' => $mobile,
    ])->exists())->toBeTrue();
})->with(['car', 'health', 'travel']);

test('add additional contact succeeds when admin accesses another advisors car, health, and travel quotes', function (string $quoteType) {
    $admin = TestDataSeeder::createAdminUser(['email' => 'admin-ac-'.Str::uuid().'@example.com']);
    $advisor = TestDataSeeder::createUserWithRole(
        match ($quoteType) {
            'car' => RolesEnum::CarAdvisor,
            'health' => RolesEnum::HealthAdvisor,
            'travel' => RolesEnum::TravelAdvisor,
            default => RolesEnum::CarAdvisor,
        },
        ['email' => 'other-advisor-'.Str::uuid().'@example.com']
    );

    $customer = createTestCustomer();

    $quote = match ($quoteType) {
        'car' => CarQuote::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'CAR-ADM-'.Str::random(6),
            'advisor_id' => $advisor->id,
            'customer_id' => $customer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'health' => HealthQuote::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'HLT-ADM-'.Str::random(6),
            'advisor_id' => $advisor->id,
            'customer_id' => $customer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'travel' => TravelQuote::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'TRV-ADM-'.Str::random(6),
            'advisor_id' => $advisor->id,
            'customer_id' => $customer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        default => throw new InvalidArgumentException($quoteType),
    };

    $mobile = '+97150'.random_int(1000000, 9999999);

    $this->actingAs($admin);

    $response = $this->postJson('/customer-additional-contact/add', [
        'customer_id' => $customer->id,
        'additional_contact_type' => GenericRequestEnum::MOBILE_NO,
        'additional_contact_val' => $mobile,
        'quote_type' => $quoteType,
        'quote_id' => $quote->id,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.message', 'Contact added successfully.');

    expect(CustomerAdditionalContact::query()->where([
        'customer_id' => $customer->id,
        'key' => GenericRequestEnum::MOBILE_NO,
        'value' => $mobile,
    ])->exists())->toBeTrue();
})->with(['car', 'health', 'travel']);

test('add additional contact succeeds when lob manager accesses another advisors car, health, and travel quotes', function (string $quoteType) {
    $managerRole = match ($quoteType) {
        'car' => RolesEnum::CarManager,
        'health' => RolesEnum::HealthManager,
        'travel' => RolesEnum::TravelManager,
        default => RolesEnum::CarManager,
    };

    $manager = TestDataSeeder::createUserWithRole($managerRole, ['email' => 'manager-'.Str::uuid().'@example.com']);
    $advisor = TestDataSeeder::createUserWithRole(
        match ($quoteType) {
            'car' => RolesEnum::CarAdvisor,
            'health' => RolesEnum::HealthAdvisor,
            'travel' => RolesEnum::TravelAdvisor,
            default => RolesEnum::CarAdvisor,
        },
        ['email' => 'managed-advisor-'.Str::uuid().'@example.com']
    );

    $customer = createTestCustomer();

    $quote = match ($quoteType) {
        'car' => CarQuote::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'CAR-MGR-'.Str::random(6),
            'advisor_id' => $advisor->id,
            'customer_id' => $customer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'health' => HealthQuote::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'HLT-MGR-'.Str::random(6),
            'advisor_id' => $advisor->id,
            'customer_id' => $customer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'travel' => TravelQuote::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'TRV-MGR-'.Str::random(6),
            'advisor_id' => $advisor->id,
            'customer_id' => $customer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        default => throw new InvalidArgumentException($quoteType),
    };

    $mobile = '+97150'.random_int(1000000, 9999999);

    $this->actingAs($manager);

    $response = $this->postJson('/customer-additional-contact/add', [
        'customer_id' => $customer->id,
        'additional_contact_type' => GenericRequestEnum::MOBILE_NO,
        'additional_contact_val' => $mobile,
        'quote_type' => $quoteType,
        'quote_id' => $quote->id,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.message', 'Contact added successfully.');

    expect(CustomerAdditionalContact::query()->where([
        'customer_id' => $customer->id,
        'key' => GenericRequestEnum::MOBILE_NO,
        'value' => $mobile,
    ])->exists())->toBeTrue();
})->with(['car', 'health', 'travel']);

test('add additional contact skips quote authorization when quote is not resolved', function (): void {
    $user = TestDataSeeder::createUser(['email' => 'no-quote-'.Str::uuid().'@example.com']);
    $customer = createTestCustomer();

    $this->actingAs($user);

    $mobile = '+97150'.random_int(1000000, 9999999);

    $response = $this->postJson('/customer-additional-contact/add', [
        'customer_id' => $customer->id,
        'additional_contact_type' => GenericRequestEnum::MOBILE_NO,
        'additional_contact_val' => $mobile,
        'quote_type' => 'car',
        'quote_id' => 999999999,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.message', 'Contact added successfully.');

    expect(CustomerAdditionalContact::query()->where([
        'customer_id' => $customer->id,
        'value' => $mobile,
    ])->exists())->toBeTrue();
});
