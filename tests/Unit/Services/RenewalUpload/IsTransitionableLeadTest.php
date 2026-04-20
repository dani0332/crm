<?php

declare(strict_types=1);

use App\Enums\InsuranceProvidersEnum;
use App\Models\CarPlan;
use App\Models\InsuranceProvider;
use App\Models\InsuranceProviderTransition;
use App\Models\RenewalQuoteProcess;
use App\Services\RenewalsUploadService;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();
});

afterEach(function () {
    Mockery::close();
});

if (! function_exists('createRenewalsUploadServiceWithMocks')) {
    /**
     * Create RenewalsUploadService instance using reflection to bypass constructor.
     */
    function createRenewalsUploadServiceWithMocks(): RenewalsUploadService
    {
        static $reflection = null;

        if ($reflection === null) {
            $reflection = new \ReflectionClass(RenewalsUploadService::class);
        }

        return $reflection->newInstanceWithoutConstructor();
    }
}

if (! function_exists('createMockLead')) {
    /**
     * Mock lead: in-memory only, no DB. data and insurance_provider_transition_id
     * are read/written via overrides; save() is a no-op.
     */
    function createMockLead(array $data, ?int $transitionId = null): RenewalQuoteProcess
    {
        $state = (object) ['insurance_provider_transition_id' => $transitionId];

        return new class($data, $state) extends RenewalQuoteProcess
        {
            protected $table = 'renewal_quote_processes';
            private array $dataStorage;
            private object $state;

            public function __construct(array $data = [], ?object $state = null)
            {
                parent::__construct();
                $this->dataStorage = $data;
                $this->state = $state ?? (object) ['insurance_provider_transition_id' => null];
            }

            public function getAttribute($key)
            {
                return match ($key) {
                    'data' => $this->dataStorage,
                    'insurance_provider_transition_id' => $this->state->insurance_provider_transition_id,
                    default => parent::getAttribute($key),
                };
            }

            public function setAttribute($key, $value)
            {
                if ($key === 'insurance_provider_transition_id') {
                    $this->state->insurance_provider_transition_id = $value;

                    return $this;
                }

                return parent::setAttribute($key, $value);
            }

            public function save(array $options = [])
            {
                return true;
            }
        };
    }
}

test('returns transitionable lead details when transition exists and plan exists', function () {
    $sourceProvider = InsuranceProvider::create([
        'code' => InsuranceProvidersEnum::RSA,
        'text' => 'RSA',
    ]);

    $targetProvider = InsuranceProvider::create([
        'code' => InsuranceProvidersEnum::AXA,
        'text' => 'AXA',
    ]);

    InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => true,
    ]);

    $plan = CarPlan::create([
        'text' => 'Smart Plan',
        'repair_type' => 'TPL',
        'provider_id' => $targetProvider->id,
    ]);

    $service = createRenewalsUploadServiceWithMocks();
    $leadValidationErrors = collect();
    $lead = createMockLead([
        'insurer' => InsuranceProvidersEnum::RSA,
        'provider_name' => $targetProvider->text,
        'plan_name' => $plan->text,
        'plan_type' => $plan->repair_type,
    ]);

    $status = $service->isTransitionableLead($lead, $leadValidationErrors);
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($status)->toBeTrue()
        ->and($result['status'])->toBeTrue()
        ->and($result['carPlan']->is($plan))->toBeTrue()
        ->and($result['insuranceProvider']->is($targetProvider))->toBeTrue()
        ->and($leadValidationErrors)->toBeEmpty();
});

test('adds validation error when transitionable lead plan is missing', function () {
    $sourceProvider = InsuranceProvider::create([
        'code' => InsuranceProvidersEnum::RSA,
        'text' => 'RSA',
    ]);

    $targetProvider = InsuranceProvider::create([
        'code' => InsuranceProvidersEnum::AXA,
        'text' => 'AXA',
    ]);

    InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => true,
    ]);

    $service = createRenewalsUploadServiceWithMocks();
    $leadValidationErrors = collect();
    $lead = createMockLead([
        'insurer' => InsuranceProvidersEnum::RSA,
        'provider_name' => $targetProvider->text,
        'plan_name' => 'Unknown Plan',
        'plan_type' => 'TPL',
    ]);

    $status = $service->isTransitionableLead($lead, $leadValidationErrors);
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($status)->toBeFalse()
        ->and($result['status'])->toBeFalse()
        ->and($result['carPlan'])->toBeNull()
        ->and($result['insuranceProvider']->is($targetProvider))->toBeTrue()
        ->and($result['tags'])->toBe('')
        ->and($leadValidationErrors)->toContain('Invalid Insurer Plan Name or Repair Type for Transitionable Lead');
});

test('returns non-transitionable details when no transition exists while plan exists', function () {
    $provider = InsuranceProvider::create([
        'code' => InsuranceProvidersEnum::AXA,
        'text' => 'AXA',
    ]);

    $plan = CarPlan::create([
        'text' => 'Comprehensive',
        'repair_type' => 'COMP',
        'provider_id' => $provider->id,
    ]);

    $service = createRenewalsUploadServiceWithMocks();
    $leadValidationErrors = collect();
    $lead = createMockLead([
        'insurer' => InsuranceProvidersEnum::AXA,
        'provider_name' => $provider->text,
        'plan_name' => $plan->text,
        'plan_type' => $plan->repair_type,
    ]);

    $status = $service->isTransitionableLead($lead, $leadValidationErrors);
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($status)->toBeFalse()
        ->and($result['status'])->toBeFalse()
        ->and($result['carPlan']->is($plan))->toBeTrue()
        ->and($result['insuranceProvider']->is($provider))->toBeTrue()
        ->and($leadValidationErrors)->toBeEmpty();
});

test('isTransitionableLead completes within 100 milliseconds', function () {
    $sourceProvider = InsuranceProvider::create([
        'code' => InsuranceProvidersEnum::RSA,
        'text' => 'RSA',
    ]);

    $targetProvider = InsuranceProvider::create([
        'code' => InsuranceProvidersEnum::AXA,
        'text' => 'AXA',
    ]);

    InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => true,
    ]);

    CarPlan::create([
        'text' => 'Quick Plan',
        'repair_type' => 'TPL',
        'provider_id' => $targetProvider->id,
    ]);

    $service = createRenewalsUploadServiceWithMocks();
    $leadValidationErrors = collect();
    $lead = createMockLead([
        'insurer' => InsuranceProvidersEnum::RSA,
        'provider_name' => $targetProvider->text,
        'plan_name' => 'Quick Plan',
        'plan_type' => 'TPL',
    ]);

    $start = microtime(true);
    $service->isTransitionableLead($lead, $leadValidationErrors);
    $duration = microtime(true) - $start;

    expect($duration)->toBeLessThan(0.1);
});

test('resolveCarPlan is memoized across isTransitionableLead and isTransitionableLeadForProcess', function () {
    $sourceProvider = InsuranceProvider::create([
        'code' => InsuranceProvidersEnum::RSA,
        'text' => 'RSA',
    ]);

    $targetProvider = InsuranceProvider::create([
        'code' => InsuranceProvidersEnum::AXA,
        'text' => 'AXA',
    ]);

    InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => true,
    ]);

    $plan = CarPlan::create([
        'text' => 'Memoized Plan',
        'repair_type' => 'TPL',
        'provider_id' => $targetProvider->id,
    ]);

    $service = createRenewalsUploadServiceWithMocks();
    $leadValidationErrors = collect();
    $lead = createMockLead([
        'insurer' => InsuranceProvidersEnum::RSA,
        'provider_name' => $targetProvider->text,
        'plan_name' => $plan->text,
        'plan_type' => $plan->repair_type,
    ]);

    $connection = DB::connection('sqlite');
    $connection->flushQueryLog();
    $connection->enableQueryLog();

    try {
        $service->isTransitionableLead($lead, $leadValidationErrors);
        $service->isTransitionableLeadForProcess($lead);

        $carPlanQueries = collect($connection->getQueryLog())
            ->filter(fn (array $entry): bool => str_contains($entry['query'], '"car_plan"'));

        expect($carPlanQueries)->toHaveCount(1);
    } finally {
        $connection->disableQueryLog();
        $connection->flushQueryLog();
    }
});

test('isTransitionableLeadForProcess does not re-query transition or providers when preceded by isTransitionableLead', function () {
    // Regression: without the per-lead resolution cache, the validation chunk
    // loop runs both functions sequentially per lead and incurs 3 redundant
    // queries (transition row + target provider + source provider) via
    // RenewalQuoteProcess::checkIsTransitionableLead()'s lazy-loaded relations.
    $sourceProvider = InsuranceProvider::create([
        'code' => InsuranceProvidersEnum::RSA,
        'text' => 'RSA',
    ]);

    $targetProvider = InsuranceProvider::create([
        'code' => InsuranceProvidersEnum::AXA,
        'text' => 'AXA',
    ]);

    InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => true,
    ]);

    $plan = CarPlan::create([
        'text' => 'Cached Plan',
        'repair_type' => 'TPL',
        'provider_id' => $targetProvider->id,
    ]);

    $service = createRenewalsUploadServiceWithMocks();
    $leadValidationErrors = collect();
    $lead = createMockLead([
        'insurer' => InsuranceProvidersEnum::RSA,
        'provider_name' => $targetProvider->text,
        'plan_name' => $plan->text,
        'plan_type' => $plan->repair_type,
    ]);

    $service->isTransitionableLead($lead, $leadValidationErrors);

    $connection = DB::connection('sqlite');
    $connection->flushQueryLog();
    $connection->enableQueryLog();

    try {
        $result = $service->isTransitionableLeadForProcess($lead);

        $queries = collect($connection->getQueryLog());
        $transitionQueries = $queries->filter(fn (array $entry): bool => str_contains($entry['query'], '"renewal_insurance_provider_transitions"'));
        $providerQueries = $queries->filter(fn (array $entry): bool => str_contains($entry['query'], '"insurance_provider"'));
        $carPlanQueries = $queries->filter(fn (array $entry): bool => str_contains($entry['query'], '"car_plan"'));

        expect($transitionQueries)->toHaveCount(0)
            ->and($providerQueries)->toHaveCount(0)
            ->and($carPlanQueries)->toHaveCount(0)
            ->and($result['status'])->toBeTrue()
            ->and($result['carPlan']->is($plan))->toBeTrue()
            ->and($result['insuranceProvider']->is($targetProvider))->toBeTrue();
    } finally {
        $connection->disableQueryLog();
        $connection->flushQueryLog();
    }
});

test('isTransitionableLeadForProcess cache is bypassed safely when transition_id changes between calls', function () {
    $sourceProvider = InsuranceProvider::create([
        'code' => InsuranceProvidersEnum::RSA,
        'text' => 'RSA',
    ]);

    $targetProvider = InsuranceProvider::create([
        'code' => InsuranceProvidersEnum::AXA,
        'text' => 'AXA',
    ]);

    $transition = InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => true,
    ]);

    $plan = CarPlan::create([
        'text' => 'Stale Cache Plan',
        'repair_type' => 'TPL',
        'provider_id' => $targetProvider->id,
    ]);

    $service = createRenewalsUploadServiceWithMocks();
    $leadValidationErrors = collect();
    $lead = createMockLead([
        'insurer' => InsuranceProvidersEnum::RSA,
        'provider_name' => $targetProvider->text,
        'plan_name' => $plan->text,
        'plan_type' => $plan->repair_type,
    ]);

    $service->isTransitionableLead($lead, $leadValidationErrors);
    expect($lead->insurance_provider_transition_id)->toBe($transition->id);

    // Simulate the lead's transition_id being externally reset (e.g. reload)
    // between the two calls. The cached entry should be treated as stale and
    // the function should recompute via the standard path.
    $lead->insurance_provider_transition_id = null;

    $result = $service->isTransitionableLeadForProcess($lead);

    expect($result['status'])->toBeFalse()
        ->and($result['transitionId'])->toBeNull()
        ->and($result['insuranceProvider']?->is($targetProvider))->toBeTrue();
});

test('getNonTransitionableLeadConfig keeps text-matched provider with null carPlan when codes do not match', function () {
    // Preserves the pre-refactor isGenesisLead() contract: a lead whose
    // provider_name resolves to a real InsuranceProvider row but whose
    // insurer code does not match that provider's code must keep the
    // text-matched provider in the result (with carPlan null). This lets
    // uploadedLeadsValidation stay on the plan-validation branch and
    // surface "Invalid Insurer Plan Name or Repair Type" instead of the
    // blanket "Invalid Insurance Provider & Provider Name Combination".
    $provider = InsuranceProvider::create([
        'code' => InsuranceProvidersEnum::AXA,
        'text' => 'AXA',
    ]);

    $service = createRenewalsUploadServiceWithMocks();
    $lead = createMockLead([
        'insurer' => InsuranceProvidersEnum::RSA, // Code mismatch: RSA vs AXA
        'provider_name' => 'AXA',
    ]);

    $result = $service->isTransitionableLeadForProcess($lead);

    expect($result['status'])->toBeFalse()
        ->and($result['insuranceProvider']?->is($provider))->toBeTrue()
        ->and($result['carPlan'])->toBeNull()
        ->and($result['transitionId'])->toBeNull()
        ->and($result['tags'])->toBe('');
});

test('getNonTransitionableLeadConfig returns null provider when provider_name does not resolve at all', function () {
    $service = createRenewalsUploadServiceWithMocks();
    $lead = createMockLead([
        'insurer' => InsuranceProvidersEnum::RSA,
        'provider_name' => 'Totally Unknown Provider Text',
    ]);

    $result = $service->isTransitionableLeadForProcess($lead);

    expect($result['status'])->toBeFalse()
        ->and($result['insuranceProvider'])->toBeNull()
        ->and($result['carPlan'])->toBeNull();
});
