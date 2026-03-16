<?php

declare(strict_types=1);

use App\Enums\FetchPlansStatuses;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Models\CarPlan;
use App\Models\CarQuote;
use App\Models\InsuranceProvider;
use App\Models\InsuranceProviderTransition;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use App\Services\RenewalsUploadService;
use Illuminate\Support\Collection;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();
});

afterEach(function () {
    Mockery::close();
});

if (! function_exists('createRenewalsUploadServiceForTransitionTests')) {
    function createRenewalsUploadServiceForTransitionTests(): RenewalsUploadService
    {
        static $reflection = null;
        if ($reflection === null) {
            $reflection = new \ReflectionClass(RenewalsUploadService::class);
        }

        return $reflection->newInstanceWithoutConstructor();
    }
}

if (! function_exists('createRenewalQuoteProcessWithDataForTransitionTests')) {
    function createRenewalQuoteProcessWithDataForTransitionTests(array $data, ?int $insuranceProviderTransitionId = null): RenewalQuoteProcess
    {
        $uploadLead = RenewalsUploadLeads::create([
            'file_name' => 'test.xlsx',
            'file_path' => 'test/path.xlsx',
            'quote_type' => 'CAR',
            'status' => 'IN_PROGRESS',
            'renewal_import_type' => RenewalsUploadType::UPDATE_LEADS,
            'is_sic' => 0,
        ]);

        $quote = CarQuote::factory()->create([
            'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        ]);

        return RenewalQuoteProcess::create([
            'renewals_upload_lead_id' => $uploadLead->id,
            'quote_id' => $quote->id,
            'quote_type' => 'CAR',
            'status' => RenewalProcessStatuses::PROCESSED,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'fetch_plans_status' => FetchPlansStatuses::FETCHED,
            'data' => $data,
            'insurance_provider_transition_id' => $insuranceProviderTransitionId,
        ]);
    }
}

// ---- isTransitionableLead ----

test('isTransitionableLead returns true and persists transition_id when active transition and matching plan exist', function () {
    $sourceProvider = InsuranceProvider::create(['code' => InsuranceProvidersEnum::RSA, 'text' => 'RSA']);
    $targetProvider = InsuranceProvider::create(['code' => InsuranceProvidersEnum::AXA, 'text' => 'GIG AXA']);

    $transition = InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => true,
    ]);

    $plan = CarPlan::create([
        'text' => 'Smart Plan',
        'repair_type' => 'TPL',
        'provider_id' => $targetProvider->id,
    ]);

    $lead = createRenewalQuoteProcessWithDataForTransitionTests([
        'insurer' => InsuranceProvidersEnum::RSA,
        'provider_name' => $targetProvider->text,
        'plan_name' => $plan->text,
        'plan_type' => $plan->repair_type,
    ]);
    $leadValidationErrors = new Collection;

    $service = createRenewalsUploadServiceForTransitionTests();
    $result = $service->isTransitionableLead($lead, $leadValidationErrors);

    expect($result)->toBeTrue()
        ->and($leadValidationErrors)->toBeEmpty();

    $lead->refresh();
    expect($lead->insurance_provider_transition_id)->toBe($transition->id);
});

test('isTransitionableLead returns false when no source provider exists for insurer code', function () {
    $targetProvider = InsuranceProvider::create(['code' => InsuranceProvidersEnum::AXA, 'text' => 'GIG AXA']);

    $lead = createRenewalQuoteProcessWithDataForTransitionTests([
        'insurer' => 'UNKNOWN_CODE',
        'provider_name' => $targetProvider->text,
        'plan_name' => 'Smart Plan',
        'plan_type' => 'TPL',
    ]);
    $leadValidationErrors = new Collection;

    $service = createRenewalsUploadServiceForTransitionTests();
    $result = $service->isTransitionableLead($lead, $leadValidationErrors);

    expect($result)->toBeFalse();
    $lead->refresh();
    expect($lead->insurance_provider_transition_id)->toBeNull();
});

test('isTransitionableLead returns false when provider_name is empty so target provider is unresolved', function () {
    InsuranceProvider::create(['code' => InsuranceProvidersEnum::RSA, 'text' => 'RSA']);

    $lead = createRenewalQuoteProcessWithDataForTransitionTests([
        'insurer' => InsuranceProvidersEnum::RSA,
        'provider_name' => '',
        'plan_name' => 'Smart Plan',
        'plan_type' => 'TPL',
    ]);
    $leadValidationErrors = new Collection;

    $service = createRenewalsUploadServiceForTransitionTests();
    $result = $service->isTransitionableLead($lead, $leadValidationErrors);

    expect($result)->toBeFalse();
});

test('isTransitionableLead returns false when no transition row exists between source and target', function () {
    $sourceProvider = InsuranceProvider::create(['code' => InsuranceProvidersEnum::RSA, 'text' => 'RSA']);
    $targetProvider = InsuranceProvider::create(['code' => InsuranceProvidersEnum::AXA, 'text' => 'GIG AXA']);

    $lead = createRenewalQuoteProcessWithDataForTransitionTests([
        'insurer' => InsuranceProvidersEnum::RSA,
        'provider_name' => $targetProvider->text,
        'plan_name' => 'Smart Plan',
        'plan_type' => 'TPL',
    ]);
    $leadValidationErrors = new Collection;

    $service = createRenewalsUploadServiceForTransitionTests();
    $result = $service->isTransitionableLead($lead, $leadValidationErrors);

    expect($result)->toBeFalse();
    $lead->refresh();
    expect($lead->insurance_provider_transition_id)->toBeNull();
});

test('isTransitionableLead returns false when transition exists but is_active is false', function () {
    $sourceProvider = InsuranceProvider::create(['code' => InsuranceProvidersEnum::RSA, 'text' => 'RSA']);
    $targetProvider = InsuranceProvider::create(['code' => InsuranceProvidersEnum::AXA, 'text' => 'GIG AXA']);

    InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => false,
    ]);

    $lead = createRenewalQuoteProcessWithDataForTransitionTests([
        'insurer' => InsuranceProvidersEnum::RSA,
        'provider_name' => $targetProvider->text,
        'plan_name' => 'Smart Plan',
        'plan_type' => 'TPL',
    ]);
    $leadValidationErrors = new Collection;

    $service = createRenewalsUploadServiceForTransitionTests();
    $result = $service->isTransitionableLead($lead, $leadValidationErrors);

    expect($result)->toBeFalse();
    $lead->refresh();
    expect($lead->insurance_provider_transition_id)->toBeNull();
});

test('isTransitionableLead adds validation error and returns false when transition is active but plan not found for target', function () {
    $sourceProvider = InsuranceProvider::create(['code' => InsuranceProvidersEnum::RSA, 'text' => 'RSA']);
    $targetProvider = InsuranceProvider::create(['code' => InsuranceProvidersEnum::AXA, 'text' => 'GIG AXA']);

    InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => true,
    ]);

    $lead = createRenewalQuoteProcessWithDataForTransitionTests([
        'insurer' => InsuranceProvidersEnum::RSA,
        'provider_name' => $targetProvider->text,
        'plan_name' => 'Unknown Plan',
        'plan_type' => 'TPL',
    ]);
    $leadValidationErrors = new Collection;

    $service = createRenewalsUploadServiceForTransitionTests();
    $result = $service->isTransitionableLead($lead, $leadValidationErrors);

    expect($result)->toBeFalse()
        ->and($leadValidationErrors)->toContain('Invalid Insurer Plan Name or Repair Type for Transitionable Lead');
});

test('isTransitionableLead accepts lead data as array and resolves correctly', function () {
    $sourceProvider = InsuranceProvider::create(['code' => InsuranceProvidersEnum::RSA, 'text' => 'RSA']);
    $targetProvider = InsuranceProvider::create(['code' => InsuranceProvidersEnum::AXA, 'text' => 'GIG AXA']);

    InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => true,
    ]);

    $plan = CarPlan::create([
        'text' => 'Array Plan',
        'repair_type' => 'COMP',
        'provider_id' => $targetProvider->id,
    ]);

    $lead = createRenewalQuoteProcessWithDataForTransitionTests([
        'insurer' => InsuranceProvidersEnum::RSA,
        'provider_name' => $targetProvider->text,
        'plan_name' => $plan->text,
        'plan_type' => $plan->repair_type,
    ]);
    $leadValidationErrors = new Collection;

    $service = createRenewalsUploadServiceForTransitionTests();
    $result = $service->isTransitionableLead($lead, $leadValidationErrors);

    expect($result)->toBeTrue();
    $lead->refresh();
    expect($lead->insurance_provider_transition_id)->not->toBeNull();
});

// ---- isTransitionableLeadForProcess ----

test('isTransitionableLeadForProcess returns transitionable config when process has active transition and target provider', function () {
    $sourceProvider = InsuranceProvider::create(['code' => InsuranceProvidersEnum::RSA, 'text' => 'RSA']);
    $targetProvider = InsuranceProvider::create(['code' => InsuranceProvidersEnum::AXA, 'text' => 'GIG AXA']);

    $transition = InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => true,
    ]);

    $plan = CarPlan::create([
        'text' => 'Smart Plan',
        'repair_type' => 'TPL',
        'provider_id' => $targetProvider->id,
    ]);

    $lead = createRenewalQuoteProcessWithDataForTransitionTests(
        [
            'insurer' => InsuranceProvidersEnum::RSA,
            'provider_name' => $targetProvider->text,
            'plan_name' => $plan->text,
            'plan_type' => $plan->repair_type,
        ],
        $transition->id
    );

    $service = createRenewalsUploadServiceForTransitionTests();
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($result['status'])->toBeTrue()
        ->and($result['insuranceProvider']->id)->toBe($targetProvider->id)
        ->and($result['carPlan']->id)->toBe($plan->id)
        ->and($result['transitionId'])->toBe($transition->id);
});

test('isTransitionableLeadForProcess returns non-transitionable config when process has no transition_id', function () {
    $provider = InsuranceProvider::create(['code' => InsuranceProvidersEnum::AXA, 'text' => 'AXA']);
    $plan = CarPlan::create([
        'text' => 'Direct Plan',
        'repair_type' => 'TPL',
        'provider_id' => $provider->id,
    ]);

    $lead = createRenewalQuoteProcessWithDataForTransitionTests([
        'insurer' => InsuranceProvidersEnum::AXA,
        'provider_name' => $provider->text,
        'plan_name' => $plan->text,
        'plan_type' => $plan->repair_type,
    ], null);

    $service = createRenewalsUploadServiceForTransitionTests();
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($result['status'])->toBeFalse()
        ->and($result['transitionId'])->toBeNull()
        ->and($result['insuranceProvider']->id)->toBe($provider->id)
        ->and($result['carPlan']->id)->toBe($plan->id);
});

test('isTransitionableLeadForProcess returns non-transitionable config when stored transition is inactive', function () {
    $sourceProvider = InsuranceProvider::create(['code' => InsuranceProvidersEnum::RSA, 'text' => 'RSA']);
    $targetProvider = InsuranceProvider::create(['code' => InsuranceProvidersEnum::AXA, 'text' => 'GIG AXA']);

    $transition = InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => false,
    ]);

    $lead = createRenewalQuoteProcessWithDataForTransitionTests(
        [
            'insurer' => InsuranceProvidersEnum::RSA,
            'provider_name' => $targetProvider->text,
            'plan_name' => 'Smart Plan',
            'plan_type' => 'TPL',
        ],
        $transition->id
    );

    $service = createRenewalsUploadServiceForTransitionTests();
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($result['status'])->toBeFalse()
        ->and($result['transitionId'])->toBeNull();
});

test('isTransitionableLeadForProcess returns non-transitionable config when transition_id points to missing record', function () {
    $lead = createRenewalQuoteProcessWithDataForTransitionTests(
        [
            'insurer' => InsuranceProvidersEnum::RSA,
            'provider_name' => 'GIG AXA',
            'plan_name' => 'Smart Plan',
            'plan_type' => 'TPL',
        ],
        99999
    );

    $service = createRenewalsUploadServiceForTransitionTests();
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($result['status'])->toBeFalse()
        ->and($result['transitionId'])->toBeNull();
});

test('isTransitionableLeadForProcess returns transitionable config with carPlan null when plan name or type do not match', function () {
    $sourceProvider = InsuranceProvider::create(['code' => InsuranceProvidersEnum::RSA, 'text' => 'RSA']);
    $targetProvider = InsuranceProvider::create(['code' => InsuranceProvidersEnum::AXA, 'text' => 'GIG AXA']);

    $transition = InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => true,
    ]);

    $lead = createRenewalQuoteProcessWithDataForTransitionTests(
        [
            'insurer' => InsuranceProvidersEnum::RSA,
            'provider_name' => $targetProvider->text,
            'plan_name' => 'NonExistent Plan',
            'plan_type' => 'COMP',
        ],
        $transition->id
    );

    $service = createRenewalsUploadServiceForTransitionTests();
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($result['status'])->toBeTrue()
        ->and($result['insuranceProvider']->id)->toBe($targetProvider->id)
        ->and($result['carPlan'])->toBeNull()
        ->and($result['transitionId'])->toBe($transition->id);
});

test('isTransitionableLeadForProcess non-transitionable path returns null carPlan when insurer code does not match provider', function () {
    $provider = InsuranceProvider::create(['code' => InsuranceProvidersEnum::AXA, 'text' => 'AXA']);
    CarPlan::create([
        'text' => 'AXA Plan',
        'repair_type' => 'TPL',
        'provider_id' => $provider->id,
    ]);

    $lead = createRenewalQuoteProcessWithDataForTransitionTests([
        'insurer' => InsuranceProvidersEnum::RSA,
        'provider_name' => $provider->text,
        'plan_name' => 'AXA Plan',
        'plan_type' => 'TPL',
    ], null);

    $service = createRenewalsUploadServiceForTransitionTests();
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($result['status'])->toBeFalse()
        ->and($result['insuranceProvider']->id)->toBe($provider->id)
        ->and($result['carPlan'])->toBeNull();
});
