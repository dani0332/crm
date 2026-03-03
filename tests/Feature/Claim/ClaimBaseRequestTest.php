<?php

use App\Http\Requests\ClaimBaseRequest;
use App\Http\Requests\ClaimStoreRequest;
use App\Http\Requests\ClaimUpdateRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    DB::table('quote_type')->insert(['id' => 1, 'code' => 'car', 'text' => 'Car', 'is_active' => 1]);
    DB::table('lookups')->insert(['id' => 1, 'key' => 'claim_type', 'text' => 'Own Damage', 'is_active' => 1]);
});

// ── Inheritance ───────────────────────────────────────────────────────────────

test('ClaimStoreRequest extends ClaimBaseRequest', function () {
    expect(ClaimStoreRequest::class)->toExtend(ClaimBaseRequest::class);
});

test('ClaimUpdateRequest extends ClaimBaseRequest', function () {
    expect(ClaimUpdateRequest::class)->toExtend(ClaimBaseRequest::class);
});

// ── Shared rules ──────────────────────────────────────────────────────────────

test('both request classes share identical rules', function () {
    $storeRules = (new ClaimStoreRequest)->rules();
    $updateRules = (new ClaimUpdateRequest)->rules();

    expect($storeRules)->toBe($updateRules);
});

test('both request classes share identical messages', function () {
    $storeMessages = (new ClaimStoreRequest)->messages();
    $updateMessages = (new ClaimUpdateRequest)->messages();

    expect($storeMessages)->toBe($updateMessages);
});

test('both request classes share identical attributes', function () {
    $storeAttributes = (new ClaimStoreRequest)->attributes();
    $updateAttributes = (new ClaimUpdateRequest)->attributes();

    expect($storeAttributes)->toBe($updateAttributes);
});

// ── Required field validation ─────────────────────────────────────────────────

it('fails when required field is missing', function (string $field) {
    $data = validClaimPayload();
    unset($data[$field]);

    $validator = Validator::make($data, (new ClaimStoreRequest)->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has($field))->toBeTrue();
})->with(['first_name', 'last_name', 'mobile_no', 'email', 'quote_type_id', 'claim_type_id']);

// ── Optional field validation ─────────────────────────────────────────────────

it('marks all optional fields as nullable in the rules', function (string $field) {
    $rules = (new ClaimStoreRequest)->rules();

    expect($rules)->toHaveKey($field)
        ->and($rules[$field])->toContain('nullable');
})->with([
    'insurance_provider_id',
    'claim_number',
    'policy_number',
    'incident_date',
    'incident_story',
    'plate_number',
    'car_make',
    'car_model',
    'model_year',
    'approved_repair_amount',
    'approved_total_loss_amount',
    'approved_cash_loss_amount',
    'claim_decline_reason',
    'claim_request_type_id',
    'service_type_id',
]);

// ── incident_date temporal validation ───────────────────────────────────────────

it('accepts today or past incident_date values when provided', function (string $incidentDate) {
    $data = array_merge(validClaimPayload(), [
        'incident_date' => $incidentDate,
    ]);

    $validator = Validator::make($data, (new ClaimStoreRequest)->rules());

    expect($validator->errors()->has('incident_date'))->toBeFalse();
})->with([
    'today' => fn () => Carbon::now()->format('Y-m-d'),
    'yesterday' => fn () => Carbon::now()->subDay()->format('Y-m-d'),
]);

// ── Log message hook ──────────────────────────────────────────────────────────

test('ClaimStoreRequest uses the correct validation-failed log message', function () {
    $message = (new ReflectionMethod(ClaimStoreRequest::class, 'validationFailedLogMessage'))
        ->invoke(new ClaimStoreRequest);

    expect($message)->toBe('Claim store validation failed');
});

test('ClaimUpdateRequest uses the correct validation-failed log message', function () {
    $message = (new ReflectionMethod(ClaimUpdateRequest::class, 'validationFailedLogMessage'))
        ->invoke(new ClaimUpdateRequest);

    expect($message)->toBe('Claim update validation failed');
});

test('ClaimStoreRequest has no extra validation-failed context', function () {
    $extra = (new ReflectionMethod(ClaimStoreRequest::class, 'validationFailedExtraContext'))
        ->invoke(new ClaimStoreRequest);

    expect($extra)->toBe([]);
});

test('ClaimUpdateRequest adds claim_uuid to validation-failed context', function () {
    $extra = (new ReflectionMethod(ClaimUpdateRequest::class, 'validationFailedExtraContext'))
        ->invoke(new ClaimUpdateRequest);

    expect($extra)->toHaveKey('claim_uuid');
});

// ── claim_decline_reason attribute label is consistent ────────────────────────

test('claim_decline_reason attribute label is consistent across both request classes', function () {
    $storeLabel = (new ClaimStoreRequest)->attributes()['claim_decline_reason'];
    $updateLabel = (new ClaimUpdateRequest)->attributes()['claim_decline_reason'];

    expect($storeLabel)->toBe('claim decline reason')
        ->and($updateLabel)->toBe($storeLabel);
});

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Minimal valid payload satisfying all required fields and exists: constraints
 * against the seeded reference data.
 *
 * @return array<string, mixed>
 */
function validClaimPayload(): array
{
    return [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'mobile_no' => '+971501234567',
        'email' => 'john.doe@example.com',
        'quote_type_id' => 1,
        'claim_type_id' => 1,
    ];
}
