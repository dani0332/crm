<?php

use App\Enums\ApplicationStorageEnums;
use App\Enums\EmbeddedTransactionEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Mail\EpFailureNotification;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Models\EmbeddedProduct;
use App\Models\EmbeddedProductOption;
use App\Models\EmbeddedTransaction;
use App\Services\SageApiEmbeddedProductService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    TestDataSeeder::seedRolePermissions(RolesEnum::EpAdmin, [PermissionsEnum::EMBEDDED_PRODUCT_SYNC_EP_BOOKING]);
});

afterEach(function () {
    Mockery::close();
});

/**
 * @return array{ep: EmbeddedProduct, carQuote: CarQuote, transaction: EmbeddedTransaction}
 */
function createSyncEpBookingFixture(array $carQuoteOverrides = [], array $transactionOverrides = []): array
{
    $ep = EmbeddedProduct::factory()->mdx()->createOneQuietly();
    $option = EmbeddedProductOption::factory()->createOneQuietly([
        'embedded_product_id' => $ep->id,
    ]);
    $carQuote = CarQuote::factory()->createOneQuietly(array_merge([
        'quote_status_id' => QuoteStatusEnum::PolicyBooked,
    ], $carQuoteOverrides));
    $transaction = EmbeddedTransaction::factory()
        ->forCarQuote($carQuote)
        ->forProduct($option->id)
        ->createOneQuietly(array_merge([
            'is_selected' => true,
            'payment_status_id' => PaymentStatusEnum::CAPTURED,
            'policy_status' => EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE,
            'sage_status_id' => null,
        ], $transactionOverrides));

    return ['ep' => $ep, 'carQuote' => $carQuote, 'transaction' => $transaction];
}

function seedEpFailureEmailApplicationStorage(): void
{
    $rows = [
        [ApplicationStorageEnums::EP_FAILURE_EMAIL_FROM, 'alfred@testnotify.alfred.ae'],
        [ApplicationStorageEnums::EP_FAILURE_EMAIL_TO, 'production.approval.team@yopmail.com'],
        [ApplicationStorageEnums::EP_FAILURE_EMAIL_REPLY_TO, 'test.emails@insurancemarket.ae'],
        [ApplicationStorageEnums::EP_FAILURE_EMAIL_CC, 'diya.lekhwani@myalfred.com'],
    ];
    foreach ($rows as [$key, $value]) {
        ApplicationStorage::query()->updateOrInsert(
            ['key_name' => $key],
            [
                'key_name' => $key,
                'value' => $value,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}

test('sync ep booking returns 403 without embedded product sync ep booking permission', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::CarNewBusinessAdvisor, ['email' => 'advisor-no-sync-ep@example.com']);
    $fixture = createSyncEpBookingFixture();

    $mock = Mockery::mock(SageApiEmbeddedProductService::class);
    $mock->shouldReceive('scheduleBookingOfEmbeddedProduct')->never();
    $this->app->instance(SageApiEmbeddedProductService::class, $mock);

    $this->actingAs($user)
        ->postJson(route('embedded-products.sync-ep-booking'), [
            'quoteId' => $fixture['carQuote']->id,
            'modelType' => 'Car',
            'epTransactionId' => $fixture['transaction']->id,
            'insuranceProviderId' => $fixture['ep']->insurance_provider_id,
        ])
        ->assertForbidden();
});

test('sync ep booking succeeds for EP admin when sage scheduling succeeds', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EpAdmin, ['email' => 'ep-admin@example.com']);
    $fixture = createSyncEpBookingFixture();

    $mock = Mockery::mock(SageApiEmbeddedProductService::class);
    $mock->shouldReceive('scheduleBookingOfEmbeddedProduct')
        ->once()
        ->andReturn(['status' => true, 'message' => 'Embedded Product Booking Process is scheduled for EP Code: '.$fixture['transaction']->code]);
    $this->app->instance(SageApiEmbeddedProductService::class, $mock);

    $this->actingAs($user)
        ->postJson(route('embedded-products.sync-ep-booking'), [
            'quoteId' => $fixture['carQuote']->id,
            'modelType' => 'Car',
            'epTransactionId' => $fixture['transaction']->id,
            'insuranceProviderId' => $fixture['ep']->insurance_provider_id,
        ])
        ->assertSuccessful()
        ->assertJson(['success' => true]);
});

test('sync ep booking returns 422 when quote is not policy booked', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EpAdmin, ['email' => 'ep-admin-2@example.com']);
    $fixture = createSyncEpBookingFixture(['quote_status_id' => QuoteStatusEnum::PolicyIssued]);

    $mock = Mockery::mock(SageApiEmbeddedProductService::class);
    $mock->shouldReceive('scheduleBookingOfEmbeddedProduct')->never();
    $this->app->instance(SageApiEmbeddedProductService::class, $mock);

    $this->actingAs($user)
        ->postJson(route('embedded-products.sync-ep-booking'), [
            'quoteId' => $fixture['carQuote']->id,
            'modelType' => 'Car',
            'epTransactionId' => $fixture['transaction']->id,
            'insuranceProviderId' => $fixture['ep']->insurance_provider_id,
        ])
        ->assertUnprocessable()
        ->assertJson(['success' => false]);
});

test('sync ep booking returns 422 when insurance provider does not match embedded product', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EpAdmin, ['email' => 'ep-admin-3@example.com']);
    $fixture = createSyncEpBookingFixture();

    $mock = Mockery::mock(SageApiEmbeddedProductService::class);
    $mock->shouldReceive('scheduleBookingOfEmbeddedProduct')->never();
    $this->app->instance(SageApiEmbeddedProductService::class, $mock);

    $this->actingAs($user)
        ->postJson(route('embedded-products.sync-ep-booking'), [
            'quoteId' => $fixture['carQuote']->id,
            'modelType' => 'Car',
            'epTransactionId' => $fixture['transaction']->id,
            'insuranceProviderId' => 999999,
        ])
        ->assertUnprocessable()
        ->assertJson(['success' => false]);
});

test('sync ep booking returns 422 when concurrent lock is held', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EpAdmin, ['email' => 'ep-admin-4@example.com']);
    $fixture = createSyncEpBookingFixture();

    Cache::put('ep-sync-sage-booking-'.$fixture['transaction']->id, true, now()->addMinutes(5));

    $mock = Mockery::mock(SageApiEmbeddedProductService::class);
    $mock->shouldReceive('scheduleBookingOfEmbeddedProduct')->never();
    $this->app->instance(SageApiEmbeddedProductService::class, $mock);

    $this->actingAs($user)
        ->postJson(route('embedded-products.sync-ep-booking'), [
            'quoteId' => $fixture['carQuote']->id,
            'modelType' => 'Car',
            'epTransactionId' => $fixture['transaction']->id,
            'insuranceProviderId' => $fixture['ep']->insurance_provider_id,
        ])
        ->assertUnprocessable()
        ->assertJson(['success' => false]);

    Cache::forget('ep-sync-sage-booking-'.$fixture['transaction']->id);
});

test('sync ep booking failure sends sage failure email even when prior ep failure email was sent', function () {
    Mail::fake();
    seedEpFailureEmailApplicationStorage();

    $user = TestDataSeeder::createUserWithRole(RolesEnum::EpAdmin, ['email' => 'ep-admin-sage-email@example.com']);
    $fixture = createSyncEpBookingFixture([], [
        'failure_email_sent_at' => now()->subDay(),
        'sage_booking_failure_email_sent_at' => null,
    ]);

    $mock = Mockery::mock(SageApiEmbeddedProductService::class);
    $mock->shouldReceive('scheduleBookingOfEmbeddedProduct')
        ->once()
        ->andReturn(['status' => false, 'message' => 'Sage booking cannot be scheduled']);
    $this->app->instance(SageApiEmbeddedProductService::class, $mock);

    $this->actingAs($user)
        ->postJson(route('embedded-products.sync-ep-booking'), [
            'quoteId' => $fixture['carQuote']->id,
            'modelType' => 'Car',
            'epTransactionId' => $fixture['transaction']->id,
            'insuranceProviderId' => $fixture['ep']->insurance_provider_id,
        ])
        ->assertUnprocessable()
        ->assertJson(['success' => false]);

    Mail::assertSent(EpFailureNotification::class, 1);

    $refreshed = $fixture['transaction']->fresh();
    expect($refreshed->sage_booking_failure_email_sent_at)->not->toBeNull();
    expect($refreshed->failure_email_sent_at)->not->toBeNull();
});

test('sync ep booking failure does not send duplicate sage failure emails', function () {
    Mail::fake();
    seedEpFailureEmailApplicationStorage();

    $user = TestDataSeeder::createUserWithRole(RolesEnum::EpAdmin, ['email' => 'ep-admin-sage-dedupe@example.com']);
    $fixture = createSyncEpBookingFixture();

    $mock = Mockery::mock(SageApiEmbeddedProductService::class);
    $mock->shouldReceive('scheduleBookingOfEmbeddedProduct')
        ->twice()
        ->andReturn(['status' => false, 'message' => 'Sage booking cannot be scheduled']);
    $this->app->instance(SageApiEmbeddedProductService::class, $mock);

    $payload = [
        'quoteId' => $fixture['carQuote']->id,
        'modelType' => 'Car',
        'epTransactionId' => $fixture['transaction']->id,
        'insuranceProviderId' => $fixture['ep']->insurance_provider_id,
    ];

    $this->actingAs($user)->postJson(route('embedded-products.sync-ep-booking'), $payload)->assertUnprocessable();
    $this->actingAs($user)->postJson(route('embedded-products.sync-ep-booking'), $payload)->assertUnprocessable();

    Mail::assertSent(EpFailureNotification::class, 1);
});

test('sync ep booking returns 422 when sage scheduling fails', function () {
    Mail::fake();
    seedEpFailureEmailApplicationStorage();

    expect(
        ApplicationStorage::query()
            ->whereIn('key_name', [
                ApplicationStorageEnums::EP_FAILURE_EMAIL_FROM,
                ApplicationStorageEnums::EP_FAILURE_EMAIL_TO,
                ApplicationStorageEnums::EP_FAILURE_EMAIL_REPLY_TO,
                ApplicationStorageEnums::EP_FAILURE_EMAIL_CC,
            ])
            ->count()
    )->toBe(4);

    $user = TestDataSeeder::createUserWithRole(RolesEnum::EpAdmin, ['email' => 'ep-admin-5@example.com']);
    $fixture = createSyncEpBookingFixture();

    $mock = Mockery::mock(SageApiEmbeddedProductService::class);
    $mock->shouldReceive('scheduleBookingOfEmbeddedProduct')
        ->once()
        ->andReturn(['status' => false, 'message' => 'Sage booking cannot be scheduled because current insurer is X']);
    $this->app->instance(SageApiEmbeddedProductService::class, $mock);

    $this->actingAs($user)
        ->postJson(route('embedded-products.sync-ep-booking'), [
            'quoteId' => $fixture['carQuote']->id,
            'modelType' => 'Car',
            'epTransactionId' => $fixture['transaction']->id,
            'insuranceProviderId' => $fixture['ep']->insurance_provider_id,
        ])
        ->assertUnprocessable()
        ->assertJson(['success' => false]);
});

test('sync ep booking is not allowed for courier embedded product', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::EpAdmin, ['email' => 'ep-admin-6@example.com']);
    $ep = EmbeddedProduct::factory()->cou()->createOneQuietly();
    $option = EmbeddedProductOption::factory()->createOneQuietly([
        'embedded_product_id' => $ep->id,
    ]);
    $carQuote = CarQuote::factory()->createOneQuietly([
        'quote_status_id' => QuoteStatusEnum::PolicyBooked,
    ]);
    $transaction = EmbeddedTransaction::factory()
        ->forCarQuote($carQuote)
        ->forProduct($option->id)
        ->createOneQuietly([
            'is_selected' => true,
            'payment_status_id' => PaymentStatusEnum::CAPTURED,
            'policy_status' => EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE,
            'sage_status_id' => null,
        ]);

    $mock = Mockery::mock(SageApiEmbeddedProductService::class);
    $mock->shouldReceive('scheduleBookingOfEmbeddedProduct')->never();
    $this->app->instance(SageApiEmbeddedProductService::class, $mock);

    $this->actingAs($user)
        ->postJson(route('embedded-products.sync-ep-booking'), [
            'quoteId' => $carQuote->id,
            'modelType' => 'Car',
            'epTransactionId' => $transaction->id,
            'insuranceProviderId' => $ep->insurance_provider_id,
        ])
        ->assertUnprocessable()
        ->assertJson(['success' => false]);
});
