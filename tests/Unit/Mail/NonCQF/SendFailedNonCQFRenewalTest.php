<?php

declare(strict_types=1);

use App\Enums\ApplicationStorageEnums;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RolesEnum;
use App\Mail\NonCQF\SendFailedNonCQFRenewal;
use App\Models\ApplicationStorage;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use App\Models\Role;
use App\Models\User;
use App\Services\CQF\NonMotor\NonCQFRenewalBrevoMailService;
use Illuminate\Support\Facades\URL;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();

    // Spatie's role() scope throws RoleDoesNotExist if the role is absent from the DB.
    Role::firstOrCreate(['name' => RolesEnum::RenewalsManager, 'guard_name' => 'web']);
});

// ─── getBrevoTemplateId ───────────────────────────────────────────────────────

test('getBrevoTemplateId returns null when key is absent', function () {
    $mail = new SendFailedNonCQFRenewal(renewalsUploadLeadsId: 1);

    expect(invokeMethod($mail, 'getBrevoTemplateId'))->toBeNull();
});

test('getBrevoTemplateId returns the configured template ID string', function () {
    ApplicationStorage::factory()->create([
        'key_name' => ApplicationStorageEnums::NON_MOTOR_CQF_FAILED_RENEWAL_BREVO_TEMPLATE,
        'value' => '42',
        'is_active' => 1,
    ]);

    $mail = new SendFailedNonCQFRenewal(renewalsUploadLeadsId: 1);

    expect(invokeMethod($mail, 'getBrevoTemplateId'))->toBe('42');
});

// ─── sendViaBrevo ─────────────────────────────────────────────────────────────

test('sendViaBrevo uses fallback template 918 when key is absent from DB', function () {
    makeRenewalsManagerUser();
    $lead = RenewalsUploadLeads::factory()->create(['quote_type' => 'HEA']);

    $emailService = Mockery::mock(NonCQFRenewalBrevoMailService::class);
    $emailService->shouldReceive('send')
        ->once()
        ->withArgs(fn (array $body) => $body['templateId'] === 918)
        ->andReturn(['sent' => 1, 'code' => 201, 'response' => '201 OK']);

    $mail = new SendFailedNonCQFRenewal(renewalsUploadLeadsId: $lead->id);

    expect($mail->sendViaBrevo($emailService))->toBeTrue();
});

test('sendViaBrevo returns false when no RenewalsManager recipients found', function () {
    $lead = RenewalsUploadLeads::factory()->create(['quote_type' => 'HEA']);

    ApplicationStorage::factory()->create([
        'key_name' => ApplicationStorageEnums::NON_MOTOR_CQF_FAILED_RENEWAL_BREVO_TEMPLATE,
        'value' => '55',
        'is_active' => 1,
    ]);

    $emailService = Mockery::mock(NonCQFRenewalBrevoMailService::class);
    $emailService->shouldNotReceive('send');

    $mail = new SendFailedNonCQFRenewal(renewalsUploadLeadsId: $lead->id);

    expect($mail->sendViaBrevo($emailService))->toBeFalse();
});

test('sendViaBrevo returns false when template ID is zero', function () {
    ApplicationStorage::factory()->create([
        'key_name' => ApplicationStorageEnums::NON_MOTOR_CQF_FAILED_RENEWAL_BREVO_TEMPLATE,
        'value' => '0',
        'is_active' => 1,
    ]);

    $emailService = Mockery::mock(NonCQFRenewalBrevoMailService::class);
    $emailService->shouldNotReceive('send');

    $mail = new SendFailedNonCQFRenewal(renewalsUploadLeadsId: 1);

    expect($mail->sendViaBrevo($emailService))->toBeFalse();
});

test('sendViaBrevo returns true when sendMail succeeds', function () {
    makeRenewalsManagerUser();

    ApplicationStorage::factory()->create([
        'key_name' => ApplicationStorageEnums::NON_MOTOR_CQF_FAILED_RENEWAL_BREVO_TEMPLATE,
        'value' => '55',
        'is_active' => 1,
    ]);

    $lead = RenewalsUploadLeads::factory()->create(['quote_type' => 'HEA']);

    URL::shouldReceive('route')->andReturn('http://localhost/fake-export-url');

    $emailService = Mockery::mock(NonCQFRenewalBrevoMailService::class);
    $emailService->shouldReceive('send')
        ->once()
        ->andReturn(['sent' => 1, 'code' => 201, 'response' => '201 OK']);

    $mail = new SendFailedNonCQFRenewal(renewalsUploadLeadsId: $lead->id);

    expect($mail->sendViaBrevo($emailService))->toBeTrue();
});

test('sendViaBrevo returns false when sendMail reports failure', function () {
    makeRenewalsManagerUser();

    ApplicationStorage::factory()->create([
        'key_name' => ApplicationStorageEnums::NON_MOTOR_CQF_FAILED_RENEWAL_BREVO_TEMPLATE,
        'value' => '55',
        'is_active' => 1,
    ]);

    $lead = RenewalsUploadLeads::factory()->create(['quote_type' => 'HEA']);

    URL::shouldReceive('route')->andReturn('http://localhost/fake-export-url');

    $emailService = Mockery::mock(NonCQFRenewalBrevoMailService::class);
    $emailService->shouldReceive('send')
        ->once()
        ->andReturn(['sent' => 0, 'code' => 400, 'response' => '400 Bad Request']);

    $mail = new SendFailedNonCQFRenewal(renewalsUploadLeadsId: $lead->id);

    expect($mail->sendViaBrevo($emailService))->toBeFalse();
});

test('sendViaBrevo returns false when sendMail throws an exception', function () {
    makeRenewalsManagerUser();

    ApplicationStorage::factory()->create([
        'key_name' => ApplicationStorageEnums::NON_MOTOR_CQF_FAILED_RENEWAL_BREVO_TEMPLATE,
        'value' => '55',
        'is_active' => 1,
    ]);

    $lead = RenewalsUploadLeads::factory()->create(['quote_type' => 'HEA']);

    URL::shouldReceive('route')->andReturn('http://localhost/fake-export-url');

    $emailService = Mockery::mock(NonCQFRenewalBrevoMailService::class);
    $emailService->shouldReceive('send')
        ->once()
        ->andThrow(new RuntimeException('Connection refused'));

    $mail = new SendFailedNonCQFRenewal(renewalsUploadLeadsId: $lead->id);

    expect($mail->sendViaBrevo($emailService))->toBeFalse();
});

test('sendViaBrevo passes correct templateId to sendMail', function () {
    makeRenewalsManagerUser();

    ApplicationStorage::factory()->create([
        'key_name' => ApplicationStorageEnums::NON_MOTOR_CQF_FAILED_RENEWAL_BREVO_TEMPLATE,
        'value' => '77',
        'is_active' => 1,
    ]);

    $lead = RenewalsUploadLeads::factory()->create(['quote_type' => 'BIK']);

    URL::shouldReceive('route')->andReturn('http://localhost/fake-export-url');

    $emailService = Mockery::mock(NonCQFRenewalBrevoMailService::class);
    $emailService->shouldReceive('send')
        ->once()
        ->withArgs(fn (array $body) => $body['templateId'] === 77)
        ->andReturn(['sent' => 1, 'code' => 201, 'response' => '201 OK']);

    $mail = new SendFailedNonCQFRenewal(renewalsUploadLeadsId: $lead->id);
    $mail->sendViaBrevo($emailService);
});

// ─── buildEmailData ───────────────────────────────────────────────────────────

test('buildEmailData returns correct structure with failed policy numbers', function () {
    $lead = RenewalsUploadLeads::factory()->create(['quote_type' => 'HEA']);

    RenewalQuoteProcess::factory()->create([
        'renewals_upload_lead_id' => $lead->id,
        'status' => RenewalProcessStatuses::BAD_DATA,
        'policy_number' => 'POL-001',
    ]);

    RenewalQuoteProcess::factory()->create([
        'renewals_upload_lead_id' => $lead->id,
        'status' => RenewalProcessStatuses::BAD_DATA,
        'policy_number' => 'POL-002',
    ]);

    URL::shouldReceive('route')->andReturn('http://localhost/fake-export-url');

    $mail = new SendFailedNonCQFRenewal(renewalsUploadLeadsId: $lead->id);
    $data = $mail->buildEmailData();

    expect($data)->toBeObject()
        ->and($data->lob)->toBe('HEA')
        ->and($data->failedLeadsCount)->toBe(2)
        ->and($data->failedQuotes)->toBe('POL-001, POL-002')
        ->and($data->attachment)->toBeArray()
        ->and($data->attachment[0])->toHaveKeys(['content', 'name'])
        ->and($data->attachment[0]['name'])->toContain('cqf-renewals-failed-leads-HEA-');
});

test('buildEmailData uses Non-motor as fallback lob when quote_type is null', function () {
    $lead = RenewalsUploadLeads::factory()->create(['quote_type' => null]);

    URL::shouldReceive('route')->andReturn('http://localhost/fake-export-url');

    $mail = new SendFailedNonCQFRenewal(renewalsUploadLeadsId: $lead->id);
    $data = $mail->buildEmailData();

    expect($data->lob)->toBe('Non-motor');
});

test('buildEmailData deduplicates policy numbers', function () {
    $lead = RenewalsUploadLeads::factory()->create(['quote_type' => 'HEA']);

    RenewalQuoteProcess::factory()->create([
        'renewals_upload_lead_id' => $lead->id,
        'status' => RenewalProcessStatuses::BAD_DATA,
        'policy_number' => 'POL-DUP',
    ]);

    RenewalQuoteProcess::factory()->create([
        'renewals_upload_lead_id' => $lead->id,
        'status' => RenewalProcessStatuses::BAD_DATA,
        'policy_number' => 'POL-DUP',
    ]);

    URL::shouldReceive('route')->andReturn('http://localhost/fake-export-url');

    $mail = new SendFailedNonCQFRenewal(renewalsUploadLeadsId: $lead->id);
    $data = $mail->buildEmailData();

    expect($data->failedLeadsCount)->toBe(1)
        ->and($data->failedQuotes)->toBe('POL-DUP');
});

test('buildEmailData includes renewals manager emails', function () {
    $lead = RenewalsUploadLeads::factory()->create(['quote_type' => 'HEA']);

    $role = Role::firstOrCreate(['name' => RolesEnum::RenewalsManager, 'guard_name' => 'web']);
    $manager = User::factory()->create(['email' => 'manager@test.com']);
    $manager->assignRole($role);

    URL::shouldReceive('route')->andReturn('http://localhost/fake-export-url');

    $mail = new SendFailedNonCQFRenewal(renewalsUploadLeadsId: $lead->id);
    $data = $mail->buildEmailData();

    expect($data->renewalsManagersEmails)->toContain('manager@test.com')
        ->and($data->renewalManagerEmail)->toBe('manager@test.com');
});

// ─── Helpers ─────────────────────────────────────────────────────────────────

function invokeMethod(object $object, string $method, array $args = []): mixed
{
    $reflection = new ReflectionMethod($object, $method);
    $reflection->setAccessible(true);

    return $reflection->invokeArgs($object, $args);
}

function makeRenewalsManagerUser(): User
{
    $role = Role::firstOrCreate(['name' => RolesEnum::RenewalsManager, 'guard_name' => 'web']);
    $user = User::factory()->create(['email' => 'renewals-manager@test.com']);
    $user->assignRole($role);

    return $user;
}
