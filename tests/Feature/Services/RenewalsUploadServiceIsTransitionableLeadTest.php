<?php

declare(strict_types=1);

use App\Enums\InsuranceProvidersTransitionEnum;
use App\Models\CarPlan;
use App\Models\InsuranceProvider;
use App\Models\InsuranceProviderTransition;
use App\Models\RenewalQuoteProcess;
use App\Services\RenewalsUploadService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

/**
 * Feature tests for RenewalsUploadService::isTransitionableLead() and
 * isTransitionableLeadForProcess() using mock data only — no application database.
 *
 * Uses in-memory SQLite (phpunit.xml DB_CONNECTION=sqlite, DB_DATABASE=:memory:).
 * TestSchemaCreator creates tables on sqlite; minimal mock data (providers, transitions,
 * plans) is created only in that in-memory DB. The lead is a fake RenewalQuoteProcess
 * that does not persist. No data is written to MySQL or any persistent database.
 *
 * Requires the PDO SQLite driver (e.g. php-sqlite3). Skipped when unavailable.
 */
beforeEach(function () {
    if (! extension_loaded('pdo_sqlite')) {
        test()->markTestSkipped('PDO SQLite driver is required for this test (no data written to application database).');
    }

    config(['database.default' => 'sqlite']);
    DB::setDefaultConnection('sqlite');

    $sqliteConfig = config('database.connections.sqlite');
    config([
        'database.connections.mysql.driver' => 'sqlite',
        'database.connections.mysql.database' => $sqliteConfig['database'] ?? ':memory:',
        'database.connections.mysql.prefix' => $sqliteConfig['prefix'] ?? '',
    ]);
    DB::purge('mysql');
    DB::reconnect('mysql');

    TestSchemaCreator::createRenewalsSchema();

    $db = DB::connection('sqlite');
    $db->table('insurance_provider')->truncate();
    $db->table('renewal_insurance_provider_transitions')->truncate();
    $db->table('car_plan')->truncate();
});

afterEach(function () {
    Mockery::close();
});

if (! function_exists('createTransitionableFeatureService')) {
    function createTransitionableFeatureService(): RenewalsUploadService
    {
        $reflection = new ReflectionClass(RenewalsUploadService::class);

        return $reflection->newInstanceWithoutConstructor();
    }
}

if (! function_exists('createTransitionableFeatureMockLead')) {
    /**
     * Mock lead: in-memory only, no DB. data and insurance_provider_transition_id
     * are read/written via overrides; save() is a no-op.
     */
    function createTransitionableFeatureMockLead(array $data, ?int $transitionId = null): RenewalQuoteProcess
    {
        $state = (object) ['insurance_provider_transition_id' => $transitionId];

        return new class($data, $state) extends RenewalQuoteProcess
        {
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

// ---- isTransitionableLead() ----

test('isTransitionableLead returns false when source provider does not exist', function () {
    $provider = InsuranceProvider::factory()->axa()->create();

    $lead = createTransitionableFeatureMockLead([
        'insurer' => 'TM',
        'provider_name' => $provider->text,
        'plan_name' => 'Plan A',
        'plan_type' => 'AGENCY',
    ]);

    $leadValidationErrors = new Collection;
    $service = createTransitionableFeatureService();
    $result = $service->isTransitionableLead($lead, $leadValidationErrors);

    expect($result)->toBeFalse()
        ->and($lead->getAttribute('insurance_provider_transition_id'))->toBeNull();
});

test('isTransitionableLead returns false when no transition or transition is inactive', function () {
    $sourceProvider = InsuranceProvider::factory()->rsa()->create();
    $targetProvider = InsuranceProvider::factory()->axa()->create();

    InsuranceProviderTransition::factory()->between($sourceProvider->id, $targetProvider->id)->create(['is_active' => false]);

    $lead = createTransitionableFeatureMockLead([
        'insurer' => 'RSA',
        'provider_name' => $targetProvider->text,
        'plan_name' => 'GIG Gulf (AXA) Motor Prestige',
        'plan_type' => 'AGENCY',
    ]);

    $leadValidationErrors = new Collection;
    $service = createTransitionableFeatureService();
    $result = $service->isTransitionableLead($lead, $leadValidationErrors);

    expect($result)->toBeFalse()
        ->and($lead->getAttribute('insurance_provider_transition_id'))->toBeNull();
});

test('isTransitionableLead adds validation error and returns false when transition active but plan not found', function () {
    $sourceProvider = InsuranceProvider::factory()->rsa()->create();
    $targetProvider = InsuranceProvider::factory()->axa()->create();

    InsuranceProviderTransition::factory()->between($sourceProvider->id, $targetProvider->id)->create();

    $lead = createTransitionableFeatureMockLead([
        'insurer' => 'RSA',
        'provider_name' => $targetProvider->text,
        'plan_name' => 'Unknown Plan',
        'plan_type' => 'TPL',
    ]);

    $leadValidationErrors = new Collection;
    $service = createTransitionableFeatureService();
    $result = $service->isTransitionableLead($lead, $leadValidationErrors);

    expect($result)->toBeFalse()
        ->and($leadValidationErrors->toArray())->toContain('Invalid Insurer Plan Name or Repair Type for Transitionable Lead');
});

test('isTransitionableLead returns true and sets transition_id when transition and plan exist', function () {
    $sourceProvider = InsuranceProvider::factory()->rsa()->create();
    $targetProvider = InsuranceProvider::factory()->axa()->create();

    $transition = InsuranceProviderTransition::factory()->between($sourceProvider->id, $targetProvider->id)->create();

    $plan = CarPlan::factory()->forInsuranceProvider($targetProvider->id)->create(['repair_type' => 'AGENCY']);

    $lead = createTransitionableFeatureMockLead([
        'insurer' => 'RSA',
        'provider_name' => $targetProvider->text,
        'plan_name' => $plan->text,
        'plan_type' => $plan->repair_type,
    ]);

    $leadValidationErrors = new Collection;
    $service = createTransitionableFeatureService();
    $result = $service->isTransitionableLead($lead, $leadValidationErrors);

    expect($result)->toBeTrue()
        ->and($lead->getAttribute('insurance_provider_transition_id'))->toBe($transition->id);
});

// ---- isTransitionableLeadForProcess() ----

test('isTransitionableLeadForProcess returns transitionable config when active transition and matching plan', function () {
    $sourceProvider = InsuranceProvider::factory()->rsa()->create();
    $targetProvider = InsuranceProvider::factory()->axa()->create();

    $transition = InsuranceProviderTransition::factory()->between($sourceProvider->id, $targetProvider->id)->create();

    $plan = CarPlan::factory()->forInsuranceProvider($targetProvider->id)->create(['repair_type' => 'AGENCY']);

    $lead = createTransitionableFeatureMockLead([
        'insurer' => 'RSA',
        'provider_name' => $targetProvider->text,
        'plan_name' => $plan->text,
        'plan_type' => $plan->repair_type,
    ], $transition->id);

    $service = createTransitionableFeatureService();
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($result['status'])->toBeTrue()
        ->and($result['insuranceProvider']->id)->toBe($targetProvider->id)
        ->and($result['carPlan']->id)->toBe($plan->id)
        ->and($result['transitionId'])->toBe($transition->id);
});

test('isTransitionableLeadForProcess handles scenario where source provider record is missing (deleted)', function () {
    $sourceProvider = InsuranceProvider::factory()->rsa()->create();
    $targetProvider = InsuranceProvider::factory()->axa()->create();

    $transition = InsuranceProviderTransition::factory()->between($sourceProvider->id, $targetProvider->id)->create();

    // Delete the source provider record but keep the transition referencing it
    $sourceProvider->delete();

    $lead = createTransitionableFeatureMockLead([
        'insurer' => 'RSA',
        'provider_name' => $targetProvider->text,
        'plan_name' => 'GIG Gulf (AXA) Motor Prestige',
        'plan_type' => 'AGENCY',
    ], $transition->id);

    $service = createTransitionableFeatureService();

    // Should NOT throw fatal error, but instead fall back to non-transitionable config
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($result['status'])->toBeFalse()
        ->and($result['transitionId'])->toBeNull();
});

test('isTransitionableLeadForProcess returns non-transitionable when no transition_id', function () {
    $provider = InsuranceProvider::factory()->axa()->create();
    $plan = CarPlan::factory()->forInsuranceProvider($provider->id)->create(['repair_type' => 'AGENCY']);

    $lead = createTransitionableFeatureMockLead([
        'insurer' => 'AXA',
        'provider_name' => $provider->text,
        'plan_name' => $plan->text,
        'plan_type' => $plan->repair_type,
    ], null);

    $service = createTransitionableFeatureService();
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($result['status'])->toBeFalse()
        ->and($result['transitionId'])->toBeNull()
        ->and($result['insuranceProvider']->id)->toBe($provider->id)
        ->and($result['tags'])->toBe('');
});

test('isTransitionableLeadForProcess correctly identifies transitionable lead even when transition_id is missing (fallback)', function () {
    $sourceProvider = InsuranceProvider::factory()->rsa()->create();
    $targetProvider = InsuranceProvider::factory()->axa()->create();

    InsuranceProviderTransition::factory()->between($sourceProvider->id, $targetProvider->id)->create();

    $plan = CarPlan::factory()->forInsuranceProvider($targetProvider->id)->create(['repair_type' => 'AGENCY']);

    // Lead has RSA insurer and AXA provider name, but NO transition_id
    $lead = createTransitionableFeatureMockLead([
        'insurer' => 'RSA',
        'provider_name' => $targetProvider->text,
        'plan_name' => $plan->text,
        'plan_type' => $plan->repair_type,
    ], null);

    $service = createTransitionableFeatureService();
    $result = $service->isTransitionableLeadForProcess($lead);

    // Fallback should find the transition and return status true
    expect($result['status'])->toBeTrue()
        ->and($result['transitionId'])->not->toBeNull()
        ->and($result['insuranceProvider']->id)->toBe($targetProvider->id)
        ->and($result['carPlan']->id)->toBe($plan->id)
        ->and($result['tags'])->toBe(InsuranceProvidersTransitionEnum::GENESIS);
});

test('isTransitionableLeadForProcess returns non-transitionable when stored transition is inactive', function () {
    $sourceProvider = InsuranceProvider::factory()->tm()->create();
    $targetProvider = InsuranceProvider::factory()->axa()->create();

    $transition = InsuranceProviderTransition::factory()->between($sourceProvider->id, $targetProvider->id)->create(['is_active' => false]);

    $lead = createTransitionableFeatureMockLead([
        'insurer' => 'TM',
        'provider_name' => $targetProvider->text,
        'plan_name' => 'GIG Gulf (AXA) Motor Prestige',
        'plan_type' => 'AGENCY',
    ], $transition->id);

    $service = createTransitionableFeatureService();
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($result['status'])->toBeFalse()
        ->and($result['transitionId'])->toBeNull();
});

test('isTransitionableLead persists transition_id when plan invalid so downstream provider combo is not misreported', function () {
    $sourceProvider = InsuranceProvider::factory()->rsa()->create();
    $targetProvider = InsuranceProvider::factory()->axa()->create();

    $transition = InsuranceProviderTransition::factory()->between($sourceProvider->id, $targetProvider->id)->create();

    $lead = createTransitionableFeatureMockLead([
        'insurer' => 'RSA',
        'provider_name' => $targetProvider->text,
        'plan_name' => 'Unknown Plan',
        'plan_type' => 'TPL',
    ]);

    $leadValidationErrors = new Collection;
    $service = createTransitionableFeatureService();

    $status = $service->isTransitionableLead($lead, $leadValidationErrors);
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($status)->toBeFalse()
        ->and($lead->getAttribute('insurance_provider_transition_id'))->toBe($transition->id)
        ->and($result['status'])->toBeFalse()
        ->and($result['insuranceProvider']->id)->toBe($targetProvider->id)
        ->and($result['carPlan'])->toBeNull()
        ->and($result['transitionId'])->toBe($transition->id)
        ->and($result['tags'])->toBe('')
        ->and($leadValidationErrors->toArray())
        ->toContain('Invalid Insurer Plan Name or Repair Type for Transitionable Lead')
        ->and($leadValidationErrors->toArray())
        ->not->toContain('Invalid Insurance Provider & Provider Name Combination Provided');
});

test('isTransitionableLead does not re-save when stored transition_id matches and differs only in scalar type (string vs int)', function () {
    // Regression: without an integer cast on insurance_provider_transition_id,
    // PDO drivers that return integer columns as strings (e.g. with
    // PDO::ATTR_EMULATE_PREPARES=true) cause $originalTransitionId to be "5"
    // while $transition->id resolves to int 5 via Eloquent's primary-key
    // auto-cast. The strict !== comparison would then trigger a save() on
    // every validation pass for already-transitionable leads. The model cast
    // normalises the value so the comparison is true.
    $sourceProvider = InsuranceProvider::factory()->rsa()->create();
    $targetProvider = InsuranceProvider::factory()->axa()->create();

    $transition = InsuranceProviderTransition::factory()->between($sourceProvider->id, $targetProvider->id)->create();

    $plan = CarPlan::factory()->forInsuranceProvider($targetProvider->id)->create(['repair_type' => 'AGENCY']);

    $state = (object) [
        'insurance_provider_transition_id' => (string) $transition->id,
        'save_count' => 0,
    ];

    $lead = new class(['insurer' => 'RSA', 'provider_name' => $targetProvider->text, 'plan_name' => $plan->text, 'plan_type' => $plan->repair_type], $state) extends RenewalQuoteProcess
    {
        private array $dataStorage;
        private object $state;

        public function __construct(array $data = [], ?object $state = null)
        {
            parent::__construct();
            $this->dataStorage = $data;
            $this->state = $state ?? (object) ['insurance_provider_transition_id' => null, 'save_count' => 0];
        }

        public function getAttribute($key)
        {
            if ($key === 'data') {
                return $this->dataStorage;
            }

            if ($key === 'insurance_provider_transition_id') {
                return $this->castAttribute($key, $this->state->insurance_provider_transition_id);
            }

            return parent::getAttribute($key);
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
            $this->state->save_count++;

            return true;
        }
    };

    $service = createTransitionableFeatureService();
    $leadValidationErrors = new Collection;

    $status = $service->isTransitionableLead($lead, $leadValidationErrors);

    expect($status)->toBeTrue()
        ->and($state->save_count)->toBe(0);
});

test('isTransitionableLeadForProcess returns status false with carPlan null when plan name or type do not match but keeps transitionId set', function () {
    $sourceProvider = InsuranceProvider::factory()->tm()->create();
    $targetProvider = InsuranceProvider::factory()->axa()->create();

    $transition = InsuranceProviderTransition::factory()->between($sourceProvider->id, $targetProvider->id)->create();

    $lead = createTransitionableFeatureMockLead([
        'insurer' => 'TM',
        'provider_name' => $targetProvider->text,
        'plan_name' => 'NonExistent Plan',
        'plan_type' => 'COMP',
        'is_gcc' => 'Yes',
    ], $transition->id);

    $service = createTransitionableFeatureService();
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($result['status'])->toBeFalse()
        ->and($result['insuranceProvider']->id)->toBe($targetProvider->id)
        ->and($result['carPlan'])->toBeNull()
        ->and($result['transitionId'])->toBe($transition->id)
        ->and($result['tags'])->toBe('');
});
