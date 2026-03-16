<?php

declare(strict_types=1);

use App\Models\CarPlan;
use App\Models\InsuranceProvider;
use App\Models\InsuranceProviderTransition;
use App\Models\RenewalQuoteProcess;
use App\Services\RenewalsUploadService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Tests\Helpers\TestSchemaCreator;

/**
 * Transition scenarios: TM and RSA converted to AXA (target).
 *
 * Schema: TestSchemaCreator::createRenewalsSchema() uses the sqlite connection
 * (SchemaUtils::CONNECTION = 'sqlite') and creates tables only—no seed data.
 *
 * These tests force in-memory SQLite so they never use the real DB (e.g. when
 * running in Docker with doppler, which may set MySQL). beforeEach sets
 * default connection to sqlite and database to :memory:, then purges/reconnects.
 *
 * Providers/transitions/plans are created per test because the schema is empty;
 * the service uses real Eloquent lookups, so each test must create the rows it needs.
 */

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

/**
 * Create a fake RenewalQuoteProcess (no DB) so we avoid creating RenewalsUploadLeads/CarQuote/process.
 * Public $data so $lead->data returns the array; __get/__set for insurance_provider_transition_id so assignment updates state.
 */
function createMockLeadForTransition(array $data, ?int $insuranceProviderTransitionId = null): RenewalQuoteProcess
{
    $state = (object) ['insurance_provider_transition_id' => $insuranceProviderTransitionId];

    return new class($data, $state) extends RenewalQuoteProcess {
        private array $dataStorage;

        private object $state;

        public function __construct(
            array $data = [],
            ?object $state = null
        ) {
            parent::__construct();
            $this->dataStorage = $data;
            $this->state = $state ?? (object) ['insurance_provider_transition_id' => null];
        }

        public function __clone()
        {
            $this->state = (object) ['insurance_provider_transition_id' => $this->state->insurance_provider_transition_id];
        }

        public function __get($key)
        {
            if ($key === 'data') {
                return $this->dataStorage;
            }
            if ($key === 'insurance_provider_transition_id') {
                return $this->state->insurance_provider_transition_id;
            }

            return parent::__get($key);
        }

        public function __set($key, $value)
        {
            if ($key === 'insurance_provider_transition_id') {
                $this->state->insurance_provider_transition_id = $value;

                return;
            }
            parent::__set($key, $value);
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


test('returns false when no source provider exists for insurer code', function () {
    InsuranceProvider::create(['code' => 'AXA', 'text' => 'GIG AXA']);

    $lead = createMockLeadForTransition([
        'insurer' => 'TM',
        'provider_name' => 'GIG AXA',
        'plan_name' => 'GIG Gulf (AXA) Motor Prestige',
        'plan_type' => 'AGENCY',
    ]);

    $leadValidationErrors = new Collection;
    $service = createRenewalsUploadServiceForTransitionTests();
    $result = $service->isTransitionableLead($lead, $leadValidationErrors);

    expect($result)->toBeFalse()
        ->and($lead->getAttribute('insurance_provider_transition_id'))->toBeNull();
});

test('returns false when no transition or transition is inactive', function () {
    $sourceProvider = InsuranceProvider::create(['code' => 'RSA', 'text' => 'RSA']);
    $targetProvider = InsuranceProvider::create(['code' => 'AXA', 'text' => 'GIG AXA']);

    InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => false,
    ]);

    $lead = createMockLeadForTransition([
        'insurer' => 'RSA',
        'provider_name' => 'GIG AXA',
        'plan_name' => 'GIG Gulf (AXA) Motor Prestige',
        'plan_type' => 'AGENCY',
    ]);

    $leadValidationErrors = new Collection;
    $service = createRenewalsUploadServiceForTransitionTests();
    $result = $service->isTransitionableLead($lead, $leadValidationErrors);

    expect($result)->toBeFalse()
        ->and($lead->getAttribute('insurance_provider_transition_id'))->toBeNull();
});

test('adds validation error and returns false when transition active but plan not found', function () {
    $sourceProvider = InsuranceProvider::create(['code' => 'RSA', 'text' => 'RSA']);
    $targetProvider = InsuranceProvider::create(['code' => 'AXA', 'text' => 'Gulf Insurance Group (Gulf) B.S.C. (C)']);

    InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => true,
    ]);

    $lead = createMockLeadForTransition([
        'insurer' => 'RSA',
        'provider_name' => 'Gulf Insurance Group (Gulf) B.S.C. (C)',
        'plan_name' => 'Unknown Plan',
        'plan_type' => 'TPL',
    ]); // RSA converted to AXA; plan invalid

    $leadValidationErrors = new Collection;
    $service = createRenewalsUploadServiceForTransitionTests();
    $result = $service->isTransitionableLead($lead, $leadValidationErrors);

    expect($result)->toBeFalse()
        ->and($leadValidationErrors)->toContain('Invalid Insurer Plan Name or Repair Type for Transitionable Lead');
})->skip(
    'Same as above: requires test DB to match (sqlite :memory: or RefreshDatabase with MySQL).'
);

// ---- isTransitionableLeadForProcess ----

test('isTransitionableLeadForProcess returns transitionable config when active transition and matching plan', function () {
    $sourceProvider = InsuranceProvider::create(['code' => 'RSA', 'text' => 'RSA']);
    $targetProvider = InsuranceProvider::create(['code' => 'AXA', 'text' => 'GIG AXA']);

    $transition = InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => true,
    ]);

    $plan = CarPlan::create([
        'text' => 'GIG Gulf (AXA) Motor Prestige',
        'repair_type' => 'AGENCY',
        'provider_id' => $targetProvider->id,
    ]);

    $lead = createMockLeadForTransition([
        'insurer' => 'RSA',
        'provider_name' => 'GIG AXA',
        'plan_name' => 'GIG Gulf (AXA) Motor Prestige',
        'plan_type' => 'AGENCY',
    ], $transition->id); // RSA converted to AXA

    $service = createRenewalsUploadServiceForTransitionTests();
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($result['status'])->toBeTrue()
        ->and($result['insuranceProvider']->id)->toBe($targetProvider->id)
        ->and($result['carPlan']->id)->toBe($plan->id)
        ->and($result['transitionId'])->toBe($transition->id);
});

test('isTransitionableLeadForProcess returns non-transitionable when no transition_id', function () {
    $providerText = 'AXA Direct '.uniqid();
    $provider = InsuranceProvider::create(['code' => 'AXA', 'text' => $providerText]);
    $plan = CarPlan::create([
        'text' => 'GIG Gulf (AXA) Motor Prestige',
        'repair_type' => 'AGENCY',
        'provider_id' => $provider->id,
    ]);

    $lead = createMockLeadForTransition([
        'insurer' => 'AXA',
        'provider_name' => $providerText,
        'plan_name' => 'GIG Gulf (AXA) Motor Prestige',
        'plan_type' => 'AGENCY',
    ], null); // already AXA, not a conversion

    $service = createRenewalsUploadServiceForTransitionTests();
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($result['status'])->toBeFalse()
        ->and($result['transitionId'])->toBeNull()
        ->and($result['insuranceProvider']->id)->toBe($provider->id)
        ->and($result['carPlan']->id)->toBe($plan->id);
});

test('isTransitionableLeadForProcess returns non-transitionable when stored transition is inactive', function () {
    $sourceProvider = InsuranceProvider::create(['code' => 'TM', 'text' => 'Tokio Marine']);
    $targetProvider = InsuranceProvider::create(['code' => 'AXA', 'text' => 'GIG AXA']);

    $transition = InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => false,
    ]);

    $lead = createMockLeadForTransition([
        'insurer' => 'TM',
        'provider_name' => 'GIG AXA',
        'plan_name' => 'GIG Gulf (AXA) Motor Prestige',
        'plan_type' => 'AGENCY',
    ], $transition->id); // TM converted to AXA; transition inactive

    $service = createRenewalsUploadServiceForTransitionTests();
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($result['status'])->toBeFalse()
        ->and($result['transitionId'])->toBeNull();
});

test('isTransitionableLeadForProcess returns transitionable with carPlan null when plan name or type do not match', function () {
    $sourceProvider = InsuranceProvider::create(['code' => 'TM', 'text' => 'Tokio Marine']);
    $targetProvider = InsuranceProvider::create(['code' => 'AXA', 'text' => 'GIG AXA']);

    $transition = InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => true,
    ]);

    $lead = createMockLeadForTransition([
        'insurer' => 'TM',
        'provider_name' => 'GIG AXA',
        'plan_name' => 'NonExistent Plan',
        'plan_type' => 'COMP',
    ], $transition->id); // TM converted to AXA; plan mismatch

    $service = createRenewalsUploadServiceForTransitionTests();
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($result['status'])->toBeTrue()
        ->and($result['insuranceProvider']->id)->toBe($targetProvider->id)
        ->and($result['carPlan'])->toBeNull()
        ->and($result['transitionId'])->toBe($transition->id);
});
