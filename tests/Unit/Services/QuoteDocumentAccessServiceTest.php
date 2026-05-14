<?php

declare(strict_types=1);

use App\Enums\AuthGuardEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\CarQuote;
use App\Models\PersonalQuote;
use App\Models\SendUpdateLog;
use App\Services\QuoteDocumentAccessService;
use App\Services\SendUpdateLogService;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

afterEach(function () {
    Mockery::close();
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
    $manager = TestDataSeeder::createUserWithRole(RolesEnum::SmartPhoneManager);
    $otherAdvisor = TestDataSeeder::createUser(['email' => 'device-assigned@example.com']);
    $quote = PersonalQuote::query()->create([
        'uuid' => Str::upper(Str::random(6)),
        'code' => 'DEV-'.Str::upper(Str::random(4)),
        'quote_type_id' => QuoteTypeId::Device,
        'advisor_id' => $otherAdvisor->id,
        'quote_status_id' => 1,
    ]);

    $service = app(QuoteDocumentAccessService::class);

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

    $service = app(QuoteDocumentAccessService::class);

    expect($service->userCanAccessQuoteDocumentable($manager, $quote))->toBeTrue();
});

test('service allows device advisor when assigned to device personal quote', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::SmartPhoneAdvisor);
    $quote = PersonalQuote::query()->create([
        'uuid' => Str::upper(Str::random(6)),
        'code' => 'DEV-'.Str::upper(Str::random(4)),
        'quote_type_id' => QuoteTypeId::Device,
        'advisor_id' => $advisor->id,
        'quote_status_id' => 1,
    ]);

    $service = app(QuoteDocumentAccessService::class);

    expect($service->userCanAccessQuoteDocumentable($advisor, $quote))->toBeTrue();
});

test('service denies device advisor when not assigned to device personal quote', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::SmartPhoneAdvisor, ['email' => 'device-a@example.com']);
    $otherAdvisor = TestDataSeeder::createUser(['email' => 'device-b@example.com']);
    $quote = PersonalQuote::query()->create([
        'uuid' => Str::upper(Str::random(6)),
        'code' => 'DEV-'.Str::upper(Str::random(4)),
        'quote_type_id' => QuoteTypeId::Device,
        'advisor_id' => $otherAdvisor->id,
        'quote_status_id' => 1,
    ]);

    $service = app(QuoteDocumentAccessService::class);

    expect($service->userCanAccessQuoteDocumentable($advisor, $quote))->toBeFalse();
});

test('service allows document-delete permission when destroy flag is true', function () {
    $assignedAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor, ['email' => 'assigned-doc-del@example.com']);
    $user = TestDataSeeder::createUser(['email' => 'document-delete-holder@example.com']);
    Permission::findOrCreate(PermissionsEnum::DOCUMENT_DELETE, AuthGuardEnum::Web->value);
    $user->givePermissionTo(PermissionsEnum::DOCUMENT_DELETE);

    $quote = CarQuote::factory()->create(['advisor_id' => $assignedAdvisor->id]);
    $service = app(QuoteDocumentAccessService::class);

    expect($service->userCanAccessQuoteDocumentable($user, $quote, forQuoteDocumentDestroy: true))->toBeTrue();
});

test('service does not apply document-delete permission when destroy flag is false', function () {
    $assignedAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor, ['email' => 'assigned-no-flag@example.com']);
    $user = TestDataSeeder::createUser(['email' => 'document-delete-only@example.com']);
    Permission::findOrCreate(PermissionsEnum::DOCUMENT_DELETE, AuthGuardEnum::Web->value);
    $user->givePermissionTo(PermissionsEnum::DOCUMENT_DELETE);

    $quote = CarQuote::factory()->create(['advisor_id' => $assignedAdvisor->id]);
    $service = app(QuoteDocumentAccessService::class);

    expect($service->userCanAccessQuoteDocumentable($user, $quote))->toBeFalse();
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

/**
 * Covers {@see QuoteDocumentAccessService} linked-quote resolution: {@see SendUpdateLogService::getQuoteObjectBy()}
 * must receive the mapped quote-type label, {@see SendUpdateLog::$quote_uuid}, and column {@code uuid}.
 */
test('service calls getQuoteObjectBy with Car label and uuid for car send update log when personal quote has no advisor', function () {
    $uuid = (string) Str::uuid();
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor);
    $linkedQuote = CarQuote::factory()->make(['uuid' => $uuid, 'advisor_id' => $advisor->id]);
    $linkedQuote->syncOriginal();

    $sendUpdateLogService = Mockery::mock(SendUpdateLogService::class);
    $sendUpdateLogService
        ->shouldReceive('getQuoteObjectBy')
        ->once()
        ->with(QuoteTypes::CAR->value, $uuid, 'uuid')
        ->andReturn($linkedQuote);

    $sendUpdateLog = SendUpdateLog::factory()->create([
        'quote_uuid' => $uuid,
        'quote_type_id' => QuoteTypeId::Car,
    ]);

    $service = new QuoteDocumentAccessService($sendUpdateLogService);

    expect($service->userCanAccessQuoteDocumentable($advisor, $sendUpdateLog))->toBeTrue();
});

test('service uses Business quote type when resolving corp-line send update log linked quote', function () {
    $uuid = (string) Str::uuid();
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::CorpLineAdvisor);
    $linkedQuote = CarQuote::factory()->make(['uuid' => $uuid, 'advisor_id' => $advisor->id]);
    $linkedQuote->syncOriginal();

    $sendUpdateLogService = Mockery::mock(SendUpdateLogService::class);
    $sendUpdateLogService
        ->shouldReceive('getQuoteObjectBy')
        ->once()
        ->with('Business', $uuid, 'uuid')
        ->andReturn($linkedQuote);

    $sendUpdateLog = SendUpdateLog::factory()->create([
        'quote_uuid' => $uuid,
        'quote_type_id' => QuoteTypeId::Corpline,
    ]);

    $service = new QuoteDocumentAccessService($sendUpdateLogService);

    expect($service->userCanAccessQuoteDocumentable($advisor, $sendUpdateLog))->toBeTrue();
});

test('service uses Business quote type when resolving group medical send update log linked quote', function () {
    $uuid = (string) Str::uuid();
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::GMAdvisor);
    $linkedQuote = CarQuote::factory()->make(['uuid' => $uuid, 'advisor_id' => $advisor->id]);
    $linkedQuote->syncOriginal();

    $sendUpdateLogService = Mockery::mock(SendUpdateLogService::class);
    $sendUpdateLogService
        ->shouldReceive('getQuoteObjectBy')
        ->once()
        ->with('Business', $uuid, 'uuid')
        ->andReturn($linkedQuote);

    $sendUpdateLog = SendUpdateLog::factory()->create([
        'quote_uuid' => $uuid,
        'quote_type_id' => QuoteTypeId::GroupMedical,
    ]);

    $service = new QuoteDocumentAccessService($sendUpdateLogService);

    expect($service->userCanAccessQuoteDocumentable($advisor, $sendUpdateLog))->toBeTrue();
});
