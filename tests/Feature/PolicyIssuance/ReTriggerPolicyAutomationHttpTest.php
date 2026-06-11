<?php

declare(strict_types=1);

use App\Enums\ApplicationStorageEnums;
use App\Enums\NgiEnum;
use App\Enums\PermissionsEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\ApplicationStorage;
use App\Models\PersonalQuote;
use App\Models\PolicyIssuance;
use App\Models\PolicyIssuanceLog;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    $this->user = TestDataSeeder::createAdminUser([], [
        PermissionsEnum::RE_TRIGGER_POLICY_AUTOMATION_DEVICE,
    ]);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->user->refresh();
    $this->actingAs($this->user);

    DB::table('insurance_provider')->insert([
        'id' => 1,
        'code' => 'NGI',
        'text' => 'National General Insurance',
        'text_lms' => null,
        'is_active' => 1,
        'is_deleted' => 0,
        'payment_gateway_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
        'deleted_at' => null,
    ]);

    ApplicationStorage::factory()->create([
        'key_name' => ApplicationStorageEnums::ENABLE_NGI_SMARTPHONE_POLICY_ISSUANCE,
        'value' => '1',
        'is_active' => 1,
    ]);

    $quoteId = DB::table('personal_quotes')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'code' => 'DEV-HTTP-'.uniqid(),
        'quote_type_id' => QuoteTypeId::Device,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->quote = PersonalQuote::query()->findOrFail($quoteId);
});

afterEach(function () {
    Mockery::close();
});

it('re-triggers policy automation when eligible', function () {
    $process = PolicyIssuance::factory()->forQuote($this->quote)->create([
        'quote_type' => QuoteTypes::DEVICE->value,
        'insurance_provider_id' => 1,
        'status' => PolicyIssuanceEnum::FAILED_STATUS,
        'completed_step' => NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
    ]);

    for ($i = 0; $i < 3; $i++) {
        PolicyIssuanceLog::query()->create([
            'policy_issuance_id' => $process->id,
            'step' => NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
            'status' => PolicyIssuanceEnum::FAILED_STATUS,
            'payload' => '[]',
            'response' => '[]',
            'endPoint' => '/test',
            'model_type' => null,
            'model_id' => null,
        ]);
    }

    $mockService = Mockery::mock(PolicyIssuanceService::class);
    $mockService->shouldReceive('shouldOfferReTriggerPolicyAutomation')
        ->once()
        ->andReturn(true);
    $mockService->shouldReceive('reTriggerPolicyAutomation')
        ->once()
        ->with(Mockery::on(fn ($arg) => $arg instanceof PolicyIssuance && $arg->id === $process->id));
    $this->app->instance(PolicyIssuanceService::class, $mockService);

    $response = $this->postJson(route('re-trigger-policy-automation'), [
        'policy_issuance_id' => $process->id,
    ]);

    $response->assertOk()
        ->assertJson(['message' => 'Policy automation re-triggered successfully.']);
});

it('returns not found when the issuance row disappears before refresh', function () {
    $process = PolicyIssuance::factory()->forQuote($this->quote)->create([
        'quote_type' => QuoteTypes::DEVICE->value,
        'insurance_provider_id' => 1,
        'status' => PolicyIssuanceEnum::FAILED_STATUS,
        'completed_step' => NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
    ]);

    for ($i = 0; $i < 3; $i++) {
        PolicyIssuanceLog::query()->create([
            'policy_issuance_id' => $process->id,
            'step' => NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
            'status' => PolicyIssuanceEnum::FAILED_STATUS,
            'payload' => '[]',
            'response' => '[]',
            'endPoint' => '/test',
            'model_type' => null,
            'model_id' => null,
        ]);
    }

    $mockService = Mockery::mock(PolicyIssuanceService::class);
    $mockService->shouldReceive('shouldOfferReTriggerPolicyAutomation')
        ->once()
        ->andReturnUsing(function (PolicyIssuance $policyIssuance): bool {
            PolicyIssuance::query()->whereKey($policyIssuance->id)->delete();

            return true;
        });
    $mockService->shouldReceive('reTriggerPolicyAutomation')->never();
    $this->app->instance(PolicyIssuanceService::class, $mockService);

    $response = $this->postJson(route('re-trigger-policy-automation'), [
        'policy_issuance_id' => $process->id,
    ]);

    $response->assertNotFound()
        ->assertJson(['message' => 'Policy issuance not found']);
});
