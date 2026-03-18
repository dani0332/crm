<?php

declare(strict_types=1);

use App\Models\CarPlan;
use App\Models\InsuranceProvider;
use App\Models\InsuranceProviderTransition;
use App\Models\RenewalQuoteProcess;
use App\Services\RenewalsUploadService;
use Illuminate\Support\Collection;
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
    Illuminate\Support\Facades\DB::setDefaultConnection('sqlite');

    $sqliteConfig = config('database.connections.sqlite');
    config([
        'database.connections.mysql.driver' => 'sqlite',
        'database.connections.mysql.database' => $sqliteConfig['database'] ?? ':memory:',
        'database.connections.mysql.prefix' => $sqliteConfig['prefix'] ?? '',
    ]);
    Illuminate\Support\Facades\DB::purge('mysql');
    Illuminate\Support\Facades\DB::reconnect('mysql');

    TestSchemaCreator::createRenewalsSchema();

    $db = Illuminate\Support\Facades\DB::connection('sqlite');
    $db->table('insurance_provider')->truncate();
    $db->table('renewal_insurance_provider_transitions')->truncate();
    $db->table('car_plan')->truncate();
});

afterEach(function () {
    Mockery::close();
});

function createServiceWithoutConstructor(): RenewalsUploadService
{
    $reflection = new ReflectionClass(RenewalsUploadService::class);

    return $reflection->newInstanceWithoutConstructor();
}

/**
 * Mock lead: in-memory only, no DB. data and insurance_provider_transition_id
 * are read/written via overrides; save() is a no-op.
 */
function createMockLead(array $data, ?int $transitionId = null): RenewalQuoteProcess
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

// ---- isTransitionableLead() ----

test('isTransitionableLead returns false when source provider does not exist', function () {
    InsuranceProvider::create(['code' => 'AXA', 'text' => 'GIG AXA']);

    $lead = createMockLead([
        'insurer' => 'TM',
        'provider_name' => 'GIG AXA',
        'plan_name' => 'Plan A',
        'plan_type' => 'AGENCY',
    ]);

    $leadValidationErrors = new Collection;
    $service = createServiceWithoutConstructor();
    $result = $service->isTransitionableLead($lead, $leadValidationErrors);

    expect($result)->toBeFalse()
        ->and($lead->getAttribute('insurance_provider_transition_id'))->toBeNull();
});

test('isTransitionableLead returns false when no transition or transition is inactive', function () {
    $sourceProvider = InsuranceProvider::create(['code' => 'RSA', 'text' => 'RSA']);
    $targetProvider = InsuranceProvider::create(['code' => 'AXA', 'text' => 'GIG AXA']);

    InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => false,
    ]);

    $lead = createMockLead([
        'insurer' => 'RSA',
        'provider_name' => 'GIG AXA',
        'plan_name' => 'GIG Gulf (AXA) Motor Prestige',
        'plan_type' => 'AGENCY',
    ]);

    $leadValidationErrors = new Collection;
    $service = createServiceWithoutConstructor();
    $result = $service->isTransitionableLead($lead, $leadValidationErrors);

    expect($result)->toBeFalse()
        ->and($lead->getAttribute('insurance_provider_transition_id'))->toBeNull();
});

test('isTransitionableLead adds validation error and returns false when transition active but plan not found', function () {
    $sourceProvider = InsuranceProvider::create(['code' => 'RSA', 'text' => 'RSA']);
    $targetProvider = InsuranceProvider::create(['code' => 'AXA', 'text' => 'Gulf Insurance Group (Gulf) B.S.C. (C)']);

    InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => true,
    ]);

    $lead = createMockLead([
        'insurer' => 'RSA',
        'provider_name' => 'Gulf Insurance Group (Gulf) B.S.C. (C)',
        'plan_name' => 'Unknown Plan',
        'plan_type' => 'TPL',
    ]);

    $leadValidationErrors = new Collection;
    $service = createServiceWithoutConstructor();
    $result = $service->isTransitionableLead($lead, $leadValidationErrors);

    expect($result)->toBeFalse()
        ->and($leadValidationErrors->toArray())->toContain('Invalid Insurer Plan Name or Repair Type for Transitionable Lead');
});

test('isTransitionableLead returns true and sets transition_id when transition and plan exist', function () {
    $sourceProvider = InsuranceProvider::create(['code' => 'RSA', 'text' => 'RSA']);
    $targetProvider = InsuranceProvider::create(['code' => 'AXA', 'text' => 'GIG AXA']);

    $transition = InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => true,
    ]);

    CarPlan::create([
        'text' => 'GIG Gulf (AXA) Motor Prestige',
        'repair_type' => 'AGENCY',
        'provider_id' => $targetProvider->id,
    ]);

    $lead = createMockLead([
        'insurer' => 'RSA',
        'provider_name' => 'GIG AXA',
        'plan_name' => 'GIG Gulf (AXA) Motor Prestige',
        'plan_type' => 'AGENCY',
    ]);

    $leadValidationErrors = new Collection;
    $service = createServiceWithoutConstructor();
    $result = $service->isTransitionableLead($lead, $leadValidationErrors);

    expect($result)->toBeTrue()
        ->and($lead->getAttribute('insurance_provider_transition_id'))->toBe($transition->id);
});

// ---- isTransitionableLeadForProcess() ----

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

    $lead = createMockLead([
        'insurer' => 'RSA',
        'provider_name' => 'GIG AXA',
        'plan_name' => 'GIG Gulf (AXA) Motor Prestige',
        'plan_type' => 'AGENCY',
    ], $transition->id);

    $service = createServiceWithoutConstructor();
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($result['status'])->toBeTrue()
        ->and($result['insuranceProvider']->id)->toBe($targetProvider->id)
        ->and($result['carPlan']->id)->toBe($plan->id)
        ->and($result['transitionId'])->toBe($transition->id);
});

test('isTransitionableLeadForProcess handles scenario where source provider record is missing (deleted)', function () {
    $sourceProvider = InsuranceProvider::create(['code' => 'RSA', 'text' => 'RSA']);
    $targetProvider = InsuranceProvider::create(['code' => 'AXA', 'text' => 'GIG AXA']);

    $transition = InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => true,
    ]);

    // Delete the source provider record but keep the transition referencing it
    $sourceProvider->delete();

    $lead = createMockLead([
        'insurer' => 'RSA',
        'provider_name' => 'GIG AXA',
        'plan_name' => 'GIG Gulf (AXA) Motor Prestige',
        'plan_type' => 'AGENCY',
    ], $transition->id);

    $service = createServiceWithoutConstructor();

    // Should NOT throw fatal error, but instead fall back to non-transitionable config
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($result['status'])->toBeFalse()
        ->and($result['transitionId'])->toBeNull();
});

test('isTransitionableLeadForProcess returns non-transitionable when no transition_id', function () {
    $providerText = 'AXA Direct '.uniqid();
    $provider = InsuranceProvider::create(['code' => 'AXA', 'text' => $providerText]);
    CarPlan::create([
        'text' => 'GIG Gulf (AXA) Motor Prestige',
        'repair_type' => 'AGENCY',
        'provider_id' => $provider->id,
    ]);

    $lead = createMockLead([
        'insurer' => 'AXA',
        'provider_name' => $providerText,
        'plan_name' => 'GIG Gulf (AXA) Motor Prestige',
        'plan_type' => 'AGENCY',
    ], null);

    $service = createServiceWithoutConstructor();
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($result['status'])->toBeFalse()
        ->and($result['transitionId'])->toBeNull()
        ->and($result['insuranceProvider']->id)->toBe($provider->id)
        ->and($result['tags'])->toBe('');
});

test('isTransitionableLeadForProcess returns non-transitionable when stored transition is inactive', function () {
    $sourceProvider = InsuranceProvider::create(['code' => 'TM', 'text' => 'Tokio Marine']);
    $targetProvider = InsuranceProvider::create(['code' => 'AXA', 'text' => 'GIG AXA']);

    $transition = InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $sourceProvider->id,
        'target_insurance_provider_id' => $targetProvider->id,
        'is_active' => false,
    ]);

    $lead = createMockLead([
        'insurer' => 'TM',
        'provider_name' => 'GIG AXA',
        'plan_name' => 'GIG Gulf (AXA) Motor Prestige',
        'plan_type' => 'AGENCY',
    ], $transition->id);

    $service = createServiceWithoutConstructor();
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

    $lead = createMockLead([
        'insurer' => 'TM',
        'provider_name' => 'GIG AXA',
        'plan_name' => 'NonExistent Plan',
        'plan_type' => 'COMP',
    ], $transition->id);

    $service = createServiceWithoutConstructor();
    $result = $service->isTransitionableLeadForProcess($lead);

    expect($result['status'])->toBeTrue()
        ->and($result['insuranceProvider']->id)->toBe($targetProvider->id)
        ->and($result['carPlan'])->toBeNull()
        ->and($result['transitionId'])->toBe($transition->id);
});
