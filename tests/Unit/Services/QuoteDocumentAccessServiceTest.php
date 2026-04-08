<?php

declare(strict_types=1);

use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Models\CarQuote;
use App\Models\PersonalQuote;
use App\Models\SendUpdateLog;
use App\Services\QuoteDocumentAccessService;
use Illuminate\Support\Str;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

test('service allows admin without matching advisor id', function () {
    $admin = TestDataSeeder::createUserWithRole(RolesEnum::Admin);
    $other = TestDataSeeder::createUser(['email' => 'other@example.com']);
    $quote = CarQuote::factory()->create(['advisor_id' => $other->id]);

    $service = app(QuoteDocumentAccessService::class);

    expect($service->userCanAccessQuoteDocumentable($admin, $quote))->toBeTrue();
});

test('service denies car advisor when not assigned to quote', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor, ['email' => 'a@example.com']);
    $otherAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor, ['email' => 'b@example.com']);
    $quote = CarQuote::factory()->create(['advisor_id' => $advisor->id]);

    $service = app(QuoteDocumentAccessService::class);

    expect($service->userCanAccessQuoteDocumentable($otherAdvisor, $quote))->toBeFalse();
});

test('service allows car advisor when assigned to quote', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor);
    $quote = CarQuote::factory()->create(['advisor_id' => $advisor->id]);

    $service = app(QuoteDocumentAccessService::class);

    expect($service->userCanAccessQuoteDocumentable($advisor, $quote))->toBeTrue();
});

test('service allows car manager when not assigned as advisor on quote', function () {
    $manager = TestDataSeeder::createUserWithRole(RolesEnum::CarManager);
    $otherAdvisor = TestDataSeeder::createUser(['email' => 'assigned@example.com']);
    $quote = CarQuote::factory()->create(['advisor_id' => $otherAdvisor->id]);

    $service = app(QuoteDocumentAccessService::class);

    expect($service->userCanAccessQuoteDocumentable($manager, $quote))->toBeTrue();
});

test('service allows device manager for device personal quote without being assigned advisor', function () {
    $manager = TestDataSeeder::createUserWithRole(RolesEnum::DeviceManager);
    $otherAdvisor = TestDataSeeder::createUser(['email' => 'device-assigned@example.com']);
    $quote = PersonalQuote::query()->create([
        'uuid' => Str::upper(Str::random(6)),
        'code' => 'DEV-'.Str::upper(Str::random(4)),
        'quote_type_id' => QuoteTypeId::Device,
        'advisor_id' => $otherAdvisor->id,
        'quote_status_id' => 1,
    ]);

    $service = new QuoteDocumentAccessService;

    expect($service->userCanAccessQuoteDocumentable($manager, $quote))->toBeTrue();
});

test('service allows smartphone manager for device personal quote without being assigned advisor', function () {
    $manager = TestDataSeeder::createUserWithRole(RolesEnum::SmartPhoneManager);
    $otherAdvisor = TestDataSeeder::createUser(['email' => 'smartphone-assigned@example.com']);
    $quote = PersonalQuote::query()->create([
        'uuid' => Str::upper(Str::random(6)),
        'code' => 'DEV-'.Str::upper(Str::random(4)),
        'quote_type_id' => QuoteTypeId::Device,
        'advisor_id' => $otherAdvisor->id,
        'quote_status_id' => 1,
    ]);

    $service = new QuoteDocumentAccessService;

    expect($service->userCanAccessQuoteDocumentable($manager, $quote))->toBeTrue();
});

test('service allows device advisor when assigned to device personal quote', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::DeviceAdvisor);
    $quote = PersonalQuote::query()->create([
        'uuid' => Str::upper(Str::random(6)),
        'code' => 'DEV-'.Str::upper(Str::random(4)),
        'quote_type_id' => QuoteTypeId::Device,
        'advisor_id' => $advisor->id,
        'quote_status_id' => 1,
    ]);

    $service = new QuoteDocumentAccessService;

    expect($service->userCanAccessQuoteDocumentable($advisor, $quote))->toBeTrue();
});

test('service denies device advisor when not assigned to device personal quote', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::DeviceAdvisor, ['email' => 'device-a@example.com']);
    $otherAdvisor = TestDataSeeder::createUser(['email' => 'device-b@example.com']);
    $quote = PersonalQuote::query()->create([
        'uuid' => Str::upper(Str::random(6)),
        'code' => 'DEV-'.Str::upper(Str::random(4)),
        'quote_type_id' => QuoteTypeId::Device,
        'advisor_id' => $otherAdvisor->id,
        'quote_status_id' => 1,
    ]);

    $service = new QuoteDocumentAccessService;

    expect($service->userCanAccessQuoteDocumentable($advisor, $quote))->toBeFalse();
});

test('service allows car advisor on send update log resolved via linked quote uuid', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor);
    $quote = CarQuote::factory()->create(['advisor_id' => $advisor->id]);
    $sendUpdateLog = SendUpdateLog::factory()->create([
        'quote_uuid' => $quote->uuid,
        'quote_type_id' => 1,
    ]);

    $service = app(QuoteDocumentAccessService::class);

    expect($service->userCanAccessQuoteDocumentable($advisor, $sendUpdateLog))->toBeTrue();
});
