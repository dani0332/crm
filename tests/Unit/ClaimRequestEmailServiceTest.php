<?php

declare(strict_types=1);

use App\Enums\ApplicationStorageEnums;
use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\QuoteTypeId;
use App\Enums\WorkflowTypeEnum;
use App\Models\ClaimRequest;
use App\Models\PersonalQuote;
use App\Models\QuoteType;
use App\Models\User;
use App\Services\EmailServices\ClaimRequestEmailService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    Config::set('database.default', 'sqlite');
    Config::set('database.connections.sqlite.database', ':memory:');
    DB::purge('sqlite');
    DB::reconnect('sqlite');

    TestSchemaCreator::createMinimalSchema();
});

/**
 * @return array{0: ClaimRequest, 1: ClaimRequestEmailService}
 */
function claimGoogleReviewEmailServiceWithClaim(
    int $quoteTypeId = QuoteTypeId::Car,
    ?PersonalQuote $personalQuote = null,
    ?int $businessTypeOfInsuranceId = null,
): array {
    $manager = new User;
    $manager->forceFill([
        'name' => 'Manager',
        'email' => 'mgr@example.com',
        'mobile_no' => '501111111',
        'landline_no' => '',
        'profile_photo_path' => '',
    ]);

    $quoteTypeLabel = match ($quoteTypeId) {
        QuoteTypeId::Health => 'Health',
        QuoteTypeId::Life => 'Life',
        QuoteTypeId::Business => 'Business',
        default => 'Car',
    };

    $quoteType = new QuoteType;
    $quoteType->forceFill(['text' => $quoteTypeLabel]);

    $claimAttributes = [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'mobile_no' => '500123456',
        'quote_type_id' => $quoteTypeId,
        'whatsapp_consent' => true,
    ];
    if ($businessTypeOfInsuranceId !== null) {
        $claimAttributes['business_type_of_insurance_id'] = $businessTypeOfInsuranceId;
    }

    $claim = new ClaimRequest;
    $claim->forceFill($claimAttributes);
    $claim->setAttribute('code', 'CLM-TEST-1');
    $claim->setRelation('manager', $manager);
    $claim->setRelation('quoteType', $quoteType);
    $claim->setRelation('personalQuote', $personalQuote);

    return [$claim, app(ClaimRequestEmailService::class)];
}

it('includes trimmed bcc from non-health google review storage key for car claims', function () {
    DB::connection('sqlite')->table('application_storage')->insert([
        'key_name' => ApplicationStorageEnums::CLAIM_GOOGLE_REVIEW_EMAIL_BCC,
        'value' => ' ops@example.com , audit@example.com ',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    [$claim, $service] = claimGoogleReviewEmailServiceWithClaim();

    $method = new ReflectionMethod(ClaimRequestEmailService::class, 'buildClaimGoogleReviewEmailData');
    $payload = $method->invoke($service, $claim);

    expect($payload->emailBcc)->toBe(['ops@example.com', 'audit@example.com']);
});

it('includes trimmed bcc from health google review storage key for health claims', function () {
    DB::connection('sqlite')->table('application_storage')->insert([
        'key_name' => ApplicationStorageEnums::CLAIM_HEALTH_GOOGLE_REVIEW_EMAIL_BCC,
        'value' => ' health-ops@example.com ',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    [$claim, $service] = claimGoogleReviewEmailServiceWithClaim(QuoteTypeId::Health);

    $method = new ReflectionMethod(ClaimRequestEmailService::class, 'buildClaimGoogleReviewEmailData');
    $payload = $method->invoke($service, $claim);

    expect($payload->emailBcc)->toBe(['health-ops@example.com']);
});

it('uses health google review bcc for business lob when claim business type is group medical', function () {
    DB::connection('sqlite')->table('application_storage')->insert([
        'key_name' => ApplicationStorageEnums::CLAIM_HEALTH_GOOGLE_REVIEW_EMAIL_BCC,
        'value' => 'business-gm-bcc@example.com',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    [$claim, $service] = claimGoogleReviewEmailServiceWithClaim(
        QuoteTypeId::Business,
        null,
        BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL,
    );

    $method = new ReflectionMethod(ClaimRequestEmailService::class, 'buildClaimGoogleReviewEmailData');
    $payload = $method->invoke($service, $claim);

    expect($payload->emailBcc)->toBe(['business-gm-bcc@example.com'])
        ->and($payload->workflowType)->toBe(WorkflowTypeEnum::CLAIM_HEALTH_GOOGLE_REVIEW_EMAIL);
});

it('uses health google review bcc when personal quote is group medical on non-health lob', function () {
    DB::connection('sqlite')->table('application_storage')->insert([
        'key_name' => ApplicationStorageEnums::CLAIM_HEALTH_GOOGLE_REVIEW_EMAIL_BCC,
        'value' => 'embedded-gm@example.com',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $personalQuote = new PersonalQuote;
    $personalQuote->forceFill(['business_type_of_insurance_id' => BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL]);

    [$claim, $service] = claimGoogleReviewEmailServiceWithClaim(QuoteTypeId::Car, $personalQuote);

    $method = new ReflectionMethod(ClaimRequestEmailService::class, 'buildClaimGoogleReviewEmailData');
    $payload = $method->invoke($service, $claim);

    expect($payload->emailBcc)->toBe(['embedded-gm@example.com'])
        ->and($payload->workflowType)->toBe(WorkflowTypeEnum::CLAIM_HEALTH_GOOGLE_REVIEW_EMAIL);
});

it('does not use non-health bcc key when claim is health workflow', function () {
    DB::connection('sqlite')->table('application_storage')->insert([
        'key_name' => ApplicationStorageEnums::CLAIM_GOOGLE_REVIEW_EMAIL_BCC,
        'value' => 'car-only@example.com',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    [$claim, $service] = claimGoogleReviewEmailServiceWithClaim(QuoteTypeId::Health);

    $method = new ReflectionMethod(ClaimRequestEmailService::class, 'buildClaimGoogleReviewEmailData');
    $payload = $method->invoke($service, $claim);

    expect($payload->emailBcc)->toBe([]);
});

it('uses empty bcc when application storage key is missing', function () {
    [$claim, $service] = claimGoogleReviewEmailServiceWithClaim();

    $method = new ReflectionMethod(ClaimRequestEmailService::class, 'buildClaimGoogleReviewEmailData');
    $payload = $method->invoke($service, $claim);

    expect($payload->emailBcc)->toBe([]);
});

it('google review bcc produces a sequential array when stored value has a leading comma', function () {
    DB::connection('sqlite')->table('application_storage')->insert([
        'key_name' => ApplicationStorageEnums::CLAIM_GOOGLE_REVIEW_EMAIL_BCC,
        'value' => ',ops@example.com',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    [$claim, $service] = claimGoogleReviewEmailServiceWithClaim();

    $method = new ReflectionMethod(ClaimRequestEmailService::class, 'buildClaimGoogleReviewEmailData');
    $payload = $method->invoke($service, $claim);

    expect($payload->emailBcc)
        ->toBe(['ops@example.com'])
        ->and(array_is_list($payload->emailBcc))->toBeTrue();
});

it('google review bcc produces a sequential array when stored value has consecutive commas', function () {
    DB::connection('sqlite')->table('application_storage')->insert([
        'key_name' => ApplicationStorageEnums::CLAIM_GOOGLE_REVIEW_EMAIL_BCC,
        'value' => 'ops@example.com,,audit@example.com',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    [$claim, $service] = claimGoogleReviewEmailServiceWithClaim();

    $method = new ReflectionMethod(ClaimRequestEmailService::class, 'buildClaimGoogleReviewEmailData');
    $payload = $method->invoke($service, $claim);

    expect($payload->emailBcc)
        ->toBe(['ops@example.com', 'audit@example.com'])
        ->and(array_is_list($payload->emailBcc))->toBeTrue();
});

it('google review bcc produces a sequential array when stored value has a trailing comma', function () {
    DB::connection('sqlite')->table('application_storage')->insert([
        'key_name' => ApplicationStorageEnums::CLAIM_GOOGLE_REVIEW_EMAIL_BCC,
        'value' => 'ops@example.com,',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    [$claim, $service] = claimGoogleReviewEmailServiceWithClaim();

    $method = new ReflectionMethod(ClaimRequestEmailService::class, 'buildClaimGoogleReviewEmailData');
    $payload = $method->invoke($service, $claim);

    expect($payload->emailBcc)
        ->toBe(['ops@example.com'])
        ->and(array_is_list($payload->emailBcc))->toBeTrue();
});

it('sub-status bcc produces a sequential array when stored value has a leading comma', function () {
    DB::connection('sqlite')->table('application_storage')->insert([
        'key_name' => ApplicationStorageEnums::CLAIM_SUB_STATUS_CUSTOMER_EMAIL_BCC_MOTOR_AND_GENERAL,
        'value' => ',motor-bcc@example.com',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    [$claim, $service] = claimGoogleReviewEmailServiceWithClaim();

    $method = new ReflectionMethod(ClaimRequestEmailService::class, 'buildClaimSubStatusCustomerUpdateEmailData');
    $payload = $method->invoke($service, $claim, 'Hello');

    expect($payload->emailBcc)
        ->toBe(['motor-bcc@example.com'])
        ->and(array_is_list($payload->emailBcc))->toBeTrue();
});

it('sub-status bcc produces a sequential array when stored value has consecutive commas', function () {
    DB::connection('sqlite')->table('application_storage')->insert([
        'key_name' => ApplicationStorageEnums::CLAIM_SUB_STATUS_CUSTOMER_EMAIL_BCC_MOTOR_AND_GENERAL,
        'value' => 'motor-bcc@example.com,,extra@example.com',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    [$claim, $service] = claimGoogleReviewEmailServiceWithClaim();

    $method = new ReflectionMethod(ClaimRequestEmailService::class, 'buildClaimSubStatusCustomerUpdateEmailData');
    $payload = $method->invoke($service, $claim, 'Hello');

    expect($payload->emailBcc)
        ->toBe(['motor-bcc@example.com', 'extra@example.com'])
        ->and(array_is_list($payload->emailBcc))->toBeTrue();
});

function insertApplicationStorageRow(string $keyName, string $value): void
{
    DB::connection('sqlite')->table('application_storage')->insert([
        'key_name' => $keyName,
        'value' => $value,
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('sub-status customer email uses motor and general bcc key', function () {
    insertApplicationStorageRow(
        ApplicationStorageEnums::CLAIM_SUB_STATUS_CUSTOMER_EMAIL_BCC_MOTOR_AND_GENERAL,
        ' motor-bcc@example.com ',
    );

    [$claim, $service] = claimGoogleReviewEmailServiceWithClaim();

    $method = new ReflectionMethod(ClaimRequestEmailService::class, 'buildClaimSubStatusCustomerUpdateEmailData');
    $payload = $method->invoke($service, $claim, 'Hello');

    expect($payload->emailBcc)->toBe(['motor-bcc@example.com']);
});

it('sub-status customer email uses health bcc for business lob with group medical business type', function () {
    insertApplicationStorageRow(
        ApplicationStorageEnums::CLAIM_SUB_STATUS_CUSTOMER_EMAIL_BCC_HEALTH,
        'business-gm-sub@example.com',
    );

    [$claim, $service] = claimGoogleReviewEmailServiceWithClaim(
        QuoteTypeId::Business,
        null,
        BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL,
    );

    $method = new ReflectionMethod(ClaimRequestEmailService::class, 'buildClaimSubStatusCustomerUpdateEmailData');
    $payload = $method->invoke($service, $claim, 'Hello');

    expect($payload->emailBcc)->toBe(['business-gm-sub@example.com'])
        ->and($payload->subject)->toContain('Claim Reimbursement');
});

it('sub-status customer email uses health bcc key for health claims', function () {
    insertApplicationStorageRow(
        ApplicationStorageEnums::CLAIM_SUB_STATUS_CUSTOMER_EMAIL_BCC_HEALTH,
        'health-a@example.com,health-b@example.com',
    );

    [$claim, $service] = claimGoogleReviewEmailServiceWithClaim(QuoteTypeId::Health);

    $method = new ReflectionMethod(ClaimRequestEmailService::class, 'buildClaimSubStatusCustomerUpdateEmailData');
    $payload = $method->invoke($service, $claim, 'Hello');

    expect($payload->emailBcc)->toBe(['health-a@example.com', 'health-b@example.com']);
});

it('sub-status customer email uses life bcc key for life claims', function () {
    insertApplicationStorageRow(
        ApplicationStorageEnums::CLAIM_SUB_STATUS_CUSTOMER_EMAIL_BCC_LIFE,
        'life-a@example.com,life-b@example.com',
    );

    [$claim, $service] = claimGoogleReviewEmailServiceWithClaim(QuoteTypeId::Life);

    $method = new ReflectionMethod(ClaimRequestEmailService::class, 'buildClaimSubStatusCustomerUpdateEmailData');
    $payload = $method->invoke($service, $claim, 'Hello');

    expect($payload->emailBcc)->toBe(['life-a@example.com', 'life-b@example.com']);
});
