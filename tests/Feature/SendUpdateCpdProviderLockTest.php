<?php

declare(strict_types=1);

use App\Enums\SendUpdateLogStatusEnum;
use App\Http\Requests\SavePolicyDetailsRequest;
use App\Http\Requests\SaveProviderDetailsRequest;
use App\Models\CarQuote;
use App\Models\Customer;
use App\Models\Lookup;
use App\Models\SendUpdateLog;
use App\Repositories\SendUpdateLogRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function (): void {
    TestSchemaCreator::createMinimalSchema();

    $policyDetailColumns = [
        'first_name' => fn (Blueprint $table) => $table->string('first_name')->nullable(),
        'last_name' => fn (Blueprint $table) => $table->string('last_name')->nullable(),
        'insurance_provider_id' => fn (Blueprint $table) => $table->unsignedBigInteger('insurance_provider_id')->nullable(),
        'plan_id' => fn (Blueprint $table) => $table->unsignedBigInteger('plan_id')->nullable(),
        'policy_number' => fn (Blueprint $table) => $table->string('policy_number')->nullable(),
        'issuance_date' => fn (Blueprint $table) => $table->date('issuance_date')->nullable(),
        'start_date' => fn (Blueprint $table) => $table->date('start_date')->nullable(),
        'expiry_date' => fn (Blueprint $table) => $table->date('expiry_date')->nullable(),
        'insurer_quote_number' => fn (Blueprint $table) => $table->string('insurer_quote_number')->nullable(),
        'issuance_status_id' => fn (Blueprint $table) => $table->unsignedBigInteger('issuance_status_id')->nullable(),
        'is_policy_filled' => fn (Blueprint $table) => $table->boolean('is_policy_filled')->default(false),
    ];

    foreach ($policyDetailColumns as $column => $definition) {
        if (! Schema::hasColumn('send_update_logs', $column)) {
            Schema::table('send_update_logs', fn (Blueprint $table) => $definition($table));
        }
    }

    $this->cpdCategory = Lookup::factory()->create(['code' => SendUpdateLogStatusEnum::CPD]);

    $customer = Customer::query()->create([
        'first_name' => 'CPD',
        'last_name' => 'Provider',
        'email' => 'cpd-provider-lock-test@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->quote = CarQuote::factory()->create(['customer_id' => $customer->id]);
});

/**
 * @return array<string, mixed>
 */
function cpdPolicyDetailsPayload(SendUpdateLog $sendUpdateLog, array $overrides = []): array
{
    return array_merge([
        'id' => $sendUpdateLog->id,
        'first_name' => 'John',
        'last_name' => 'Smith',
        'policy_number' => 'PN-12345',
        'issuance_date' => '2026-01-01',
        'start_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'plan_id' => null,
        'insurer_quote_number' => 'IQN-1',
        'issuance_status_id' => 1,
    ], $overrides);
}

test('savePolicyDetails never updates the provider even when a different one is submitted', function (): void {
    $sendUpdateLog = SendUpdateLog::factory()->create([
        'category_id' => $this->cpdCategory->id,
        'quote_uuid' => $this->quote->uuid,
        'insurance_provider_id' => 55,
    ]);

    $result = SendUpdateLogRepository::savePolicyDetails(
        cpdPolicyDetailsPayload($sendUpdateLog, ['insurance_provider_id' => 99]),
    );

    expect($result)->toBeTrue();

    $sendUpdateLog->refresh();
    expect($sendUpdateLog->insurance_provider_id)->toBe(55)
        ->and($sendUpdateLog->first_name)->toBe('John')
        ->and((bool) $sendUpdateLog->is_policy_filled)->toBeTrue();
});

test('savePolicyDetails marks the policy as not filled when the quote has no provider', function (): void {
    $sendUpdateLog = SendUpdateLog::factory()->create([
        'category_id' => $this->cpdCategory->id,
        'quote_uuid' => $this->quote->uuid,
        'insurance_provider_id' => null,
    ]);

    $result = SendUpdateLogRepository::savePolicyDetails(cpdPolicyDetailsPayload($sendUpdateLog));

    expect($result)->toBeTrue();

    $sendUpdateLog->refresh();
    expect($sendUpdateLog->insurance_provider_id)->toBeNull()
        ->and((bool) $sendUpdateLog->is_policy_filled)->toBeFalse();
});

test('saveProviderDetails request is rejected for a CPD send update', function (): void {
    $sendUpdateLog = SendUpdateLog::factory()->create([
        'category_id' => $this->cpdCategory->id,
        'quote_uuid' => $this->quote->uuid,
        'insurance_provider_id' => 55,
    ]);

    $request = new SaveProviderDetailsRequest;
    $request->merge([
        'insurance_provider_id' => 99,
        'send_update_log_id' => $sendUpdateLog->id,
    ]);

    $validator = Validator::make($request->all(), $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('error'))->toContain('cannot be changed');
});

test('saveProviderDetails request is still allowed for a non-CPD send update', function (): void {
    $efCategory = Lookup::factory()->create(['code' => SendUpdateLogStatusEnum::EF]);
    $sendUpdateLog = SendUpdateLog::factory()->create([
        'category_id' => $efCategory->id,
        'quote_uuid' => $this->quote->uuid,
    ]);

    $request = new SaveProviderDetailsRequest;
    $request->merge([
        'insurance_provider_id' => 99,
        'send_update_log_id' => $sendUpdateLog->id,
    ]);

    $validator = Validator::make($request->all(), $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeFalse();
});

test('savePolicyDetails request no longer accepts provider fields', function (): void {
    $sendUpdateLog = SendUpdateLog::factory()->create([
        'category_id' => $this->cpdCategory->id,
        'quote_uuid' => $this->quote->uuid,
    ]);

    $request = new SavePolicyDetailsRequest;
    $request->merge(['id' => $sendUpdateLog->id]);

    $rules = $request->rules();

    expect($rules)->not->toHaveKey('insurance_provider_id')
        ->and($rules)->not->toHaveKey('provider_name');
});
