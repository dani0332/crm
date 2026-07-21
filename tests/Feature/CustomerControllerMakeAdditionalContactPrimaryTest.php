<?php

declare(strict_types=1);

use App\Enums\ApplicationStorageEnums;
use App\Enums\GenericRequestEnum;
use App\Enums\PermissionsEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Enums\SLAActionTypeEnum;
use App\Http\Middleware\CheckRouteAccess;
use App\Http\Middleware\PreventRequestForgery;
use App\Models\ApplicationStorage;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\Customer;
use App\Models\InsuranceProvider;
use App\Models\Payment;
use App\Models\PolicyIssuance;
use App\Services\CustomerService;
use App\Services\SLA\SLAService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;
use Tests\Support\Schema\SchemaUtils;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    SchemaUtils::ensureTable('business_quote_request', function (Blueprint $table) {
        $table->id();
        $table->uuid('uuid')->unique();
        $table->string('code')->nullable();
        $table->unsignedBigInteger('business_type_of_insurance_id')->nullable();
        $table->string('email')->nullable();
        $table->unsignedBigInteger('advisor_id')->nullable();
        $table->unsignedBigInteger('quote_status_id')->nullable();
        $table->unsignedBigInteger('customer_id')->nullable();
        $table->timestamps();
    });

    $this->withoutMiddleware([
        PreventRequestForgery::class,
        CheckRouteAccess::class,
    ]);
});

test('make additional contact primary returns 403 for business quote when user fails business quote permission', function () {
    $this->mock(CustomerService::class, function ($mock) {
        $mock->shouldNotReceive('makeAdditionalContactPrimary');
    });

    $assignedAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor, ['email' => 'assigned-bq@example.com']);
    $otherUser = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor, ['email' => 'other-bq@example.com']);

    $quote = BusinessQuote::query()->create([
        'uuid' => (string) Str::uuid(),
        'code' => 'BQ-PERM-1',
        'business_type_of_insurance_id' => 1,
        'email' => 'biz@example.com',
        'advisor_id' => $assignedAdvisor->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($otherUser);

    $response = $this->postJson('/customer-additional-contact/0/make-primary', [
        'quote_id' => $quote->id,
        'quote_type' => 'business',
        'key' => GenericRequestEnum::MOBILE_NO,
        'value' => '+971500000000',
    ]);

    $response->assertForbidden();
    $response->assertJsonPath('error.message', 'You are not authorized to update the primary contact for this quote.');
});

test('make additional contact primary skips business permission for car quote and delegates to customer service', function () {
    $this->mock(CustomerService::class, function ($mock) {
        $mock->shouldReceive('makeAdditionalContactPrimary')
            ->once()
            ->withArgs(function ($quote, $key, $value, $keepExisting) {
                return $quote instanceof CarQuote
                    && $key === GenericRequestEnum::MOBILE_NO
                    && $value === '+971501111111'
                    && $keepExisting === true;
            });
    });

    $this->mock(SLAService::class, function ($mock) {
        $mock->shouldReceive('meetSLAOnEdit')
            ->once()
            ->withArgs(function ($quote, $action) {
                return $quote instanceof CarQuote && $action === SLAActionTypeEnum::ADDITIONAL_CONTACTS_PRIMARY_UPDATE;
            });
    });

    $customer = Customer::factory()->create();
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor, ['email' => 'car-adv@example.com']);
    $quote = CarQuote::factory()
        ->forCustomer($customer->id)
        ->create(['advisor_id' => $advisor->id]);

    $this->actingAs($advisor);

    $response = $this->postJson('/customer-additional-contact/0/make-primary', [
        'quote_id' => $quote->id,
        'quote_type' => 'car',
        'key' => GenericRequestEnum::MOBILE_NO,
        'value' => '+971501111111',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.message', 'Primary Contact Updated');
});

test('make additional contact primary blocks email change while policy issuance automation is locked', function () {
    $this->mock(CustomerService::class, function ($mock) {
        $mock->shouldNotReceive('makeAdditionalContactPrimary');
    });

    ApplicationStorage::factory()->create([
        'key_name' => ApplicationStorageEnums::ENABLE_LIVA_CAR_POLICY_ISSUANCE,
        'value' => '1',
        'is_active' => 1,
    ]);

    $insuranceProvider = InsuranceProvider::factory()->rsa()->create();
    $customer = Customer::factory()->create();
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor, ['email' => 'car-adv-locked@example.com']);
    $quote = CarQuote::factory()
        ->forCustomer($customer->id)
        ->create(['advisor_id' => $advisor->id]);

    $this->actingAs($advisor);

    Payment::withoutEvents(fn () => Payment::factory()->createForSqlite($quote, [
        'insurance_provider_id' => $insuranceProvider->id,
    ]));

    PolicyIssuance::factory()->create([
        'model_type' => $quote->getMorphClass(),
        'model_id' => $quote->id,
        'insurance_provider_id' => $insuranceProvider->id,
        'quote_type' => 'car',
        'status' => PolicyIssuanceEnum::PENDING_STATUS,
        'completed_step' => null,
    ]);

    $response = $this->postJson('/customer-additional-contact/0/make-primary', [
        'quote_id' => $quote->id,
        'quote_type' => 'car',
        'key' => GenericRequestEnum::EMAIL,
        'value' => 'new-primary@example.com',
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment(['error' => ['Primary email ID cannot be changed while the policy booking is in progress.']]);
});

test('make additional contact primary allows email change while policy issuance is locked when user has manual override permission', function () {
    $this->mock(CustomerService::class, function ($mock) {
        $mock->shouldReceive('makeAdditionalContactPrimary')
            ->once()
            ->withArgs(function ($quote, $key, $value) {
                return $quote instanceof CarQuote
                    && $key === GenericRequestEnum::EMAIL
                    && $value === 'new-primary@example.com';
            });
    });

    $this->mock(SLAService::class, function ($mock) {
        $mock->shouldReceive('meetSLAOnEdit')->once();
    });

    ApplicationStorage::factory()->create([
        'key_name' => ApplicationStorageEnums::ENABLE_LIVA_CAR_POLICY_ISSUANCE,
        'value' => '1',
        'is_active' => 1,
    ]);

    $insuranceProvider = InsuranceProvider::factory()->rsa()->create();
    $customer = Customer::factory()->create();
    $advisor = TestDataSeeder::createAdminUser(
        ['email' => 'car-adv-override@example.com'],
        [PermissionsEnum::ADDITIONAL_CONTACT_MANUAL_OVERRIDE]
    );
    $quote = CarQuote::factory()
        ->forCustomer($customer->id)
        ->create(['advisor_id' => $advisor->id]);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $advisor->refresh();
    $this->actingAs($advisor);

    Payment::withoutEvents(fn () => Payment::factory()->createForSqlite($quote, [
        'insurance_provider_id' => $insuranceProvider->id,
    ]));

    PolicyIssuance::factory()->create([
        'model_type' => $quote->getMorphClass(),
        'model_id' => $quote->id,
        'insurance_provider_id' => $insuranceProvider->id,
        'quote_type' => 'car',
        'status' => PolicyIssuanceEnum::PENDING_STATUS,
        'completed_step' => null,
    ]);

    $response = $this->postJson('/customer-additional-contact/0/make-primary', [
        'quote_id' => $quote->id,
        'quote_type' => 'car',
        'key' => GenericRequestEnum::EMAIL,
        'value' => 'new-primary@example.com',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.message', 'Primary Contact Updated');
});

test('make additional contact primary allows email change while quote status is policy booking queued when user has manual override permission', function () {
    $this->mock(CustomerService::class, function ($mock) {
        $mock->shouldReceive('makeAdditionalContactPrimary')
            ->once()
            ->withArgs(function ($quote, $key, $value) {
                return $quote instanceof CarQuote
                    && $key === GenericRequestEnum::EMAIL
                    && $value === 'new-primary@example.com';
            });
    });

    $this->mock(SLAService::class, function ($mock) {
        $mock->shouldReceive('meetSLAOnEdit')->once();
    });

    $customer = Customer::factory()->create();
    $advisor = TestDataSeeder::createAdminUser(
        ['email' => 'car-adv-queued-override@example.com'],
        [PermissionsEnum::ADDITIONAL_CONTACT_MANUAL_OVERRIDE]
    );
    $quote = CarQuote::factory()
        ->forCustomer($customer->id)
        ->create([
            'advisor_id' => $advisor->id,
            'quote_status_id' => QuoteStatusEnum::POLICY_BOOKING_QUEUED,
        ]);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $advisor->refresh();
    $this->actingAs($advisor);

    $response = $this->postJson('/customer-additional-contact/0/make-primary', [
        'quote_id' => $quote->id,
        'quote_type' => 'car',
        'key' => GenericRequestEnum::EMAIL,
        'value' => 'new-primary@example.com',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.message', 'Primary Contact Updated');
});
