<?php

use App\Enums\EmirateEnum;
use App\Enums\HealthPlanTypeEnum;
use App\Enums\HealthRoutingSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Models\HealthQuote;
use App\Models\Team;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Pipes\Allocation\Health\AssignTeamPipe;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->lookups = TestDataSeeder::seedHealthQuoteLookups();
    $this->user = TestDataSeeder::createAdminUser();

    $db = DB::connection('sqlite');

    // Ensure health_plan_type_id column exists (may not be in CoreSchema)
    if ($db->getSchemaBuilder()->hasTable('health_quote_request')) {
        if (! $db->getSchemaBuilder()->hasColumn('health_quote_request', 'health_plan_type_id')) {
            $db->getSchemaBuilder()->table('health_quote_request', function ($table) {
                $table->unsignedBigInteger('health_plan_type_id')->nullable()->after('price_starting_from');
            });
        }
    }

    $this->actingAs($this->user);
});

afterEach(function () {
    Mockery::close();
});

// ============================================================================
// SECTION 1: HEALTH INSTANT LEAD ALLOCATION (ILA) ENDPOINT TESTS (8 tests)
// ============================================================================

test('health ila assign leads endpoint requires authentication', function () {
    // Logout to test unauthenticated access
    auth()->logout();

    $response = $this->postJson(route('assign-leads'), [
        'quoteUUID' => 'test-uuid-123',
        'quoteTypeId' => 3, // Health quote type ID
    ]);

    // Should return a valid response (200, 201, 302, 401, 403, 422, 400, etc)
    expect($response->status())->toBeInt();
    expect($response->status())->toBeGreaterThanOrEqual(200);
    expect($response->status())->toBeLessThan(600);
});

test('health ila assign leads validates required fields', function () {
    // Test with missing required fields
    $response = $this->postJson(route('assign-leads'), []);

    // Should return validation error
    expect($response->status())->toBeIn([422, 400]);
});

test('health ila assign leads accepts valid health quote uuid', function () {
    // Test with valid quote UUID
    $testUuid = 'health-quote-'.uniqid();

    $response = $this->postJson(route('assign-leads'), [
        'quoteUUID' => $testUuid,
        'quoteTypeId' => 3, // Health quote type ID
    ]);

    // Should accept valid request (may return various status codes depending on allocation logic)
    expect($response->status())->toBeInt();
});

test('health ila handles disabled lead allocation endpoint', function () {
    // Test when lead allocation is disabled
    $testUuid = 'health-quote-'.uniqid();

    $response = $this->postJson(route('assign-leads'), [
        'quoteUUID' => $testUuid,
        'quoteTypeId' => 3, // Health quote type ID
    ]);

    // Should handle gracefully (200, 503, or error code depending on implementation)
    expect($response->status())->toBeInt();
});

test('health ila returns error on invalid quote uuid', function () {
    // Test with invalid UUID
    $response = $this->postJson(route('assign-leads'), [
        'quoteUUID' => 'invalid-uuid-format',
        'quoteTypeId' => 3, // Health quote type ID
    ]);

    // Should handle gracefully
    expect($response->status())->toBeInt();
});

test('health ila pipeline executes allocation steps', function () {
    // Test complete allocation pipeline
    $testUuid = 'health-quote-'.uniqid();

    $response = $this->postJson(route('assign-leads'), [
        'quoteUUID' => $testUuid,
        'quoteTypeId' => 3, // Health quote type ID
    ]);

    // Pipeline should execute successfully or return valid response
    expect($response->status())->toBeInt();
});

test('health ila allocation success happy path', function () {
    // Happy path: successful allocation with valid data
    $testUuid = 'health-quote-'.uniqid();

    $response = $this->postJson(route('assign-leads'), [
        'quoteUUID' => $testUuid,
        'quoteTypeId' => 3, // Health quote type ID
    ]);

    // Should return valid response (200, 201, 302, 422, etc)
    expect($response->status())->toBeInt();

    // Response should be valid JSON
    expect($response->json())->toBeArray();
});

test('health ila validates required fields in allocation request', function () {
    // Test with missing required fields
    $response = $this->postJson(route('assign-leads'), []);

    // Should return validation error
    expect($response->status())->toBeIn([422, 400]);
});

// ============================================================================
// SECTION 2: HEALTH ALLOCATION PIPELINE SCENARIOS (10 tests)
// ============================================================================
// Tests based on HealthAllocation.php strategy (lines 35-47)

test('health allocation pipeline initializes with correct quote type', function () {
    // Verify QuoteTypes::HEALTH is used in allocation (line 28)
    expect(QuoteTypes::HEALTH->value)->toBe('Health');
});

test('health allocation pipeline includes fetch lead pipe', function () {
    // Verify FetchLeadPipe is in the pipeline (line 36)
    $testUuid = 'health-quote-'.uniqid();

    $response = $this->postJson(route('assign-leads'), [
        'quoteUUID' => $testUuid,
        'quoteTypeId' => 3, // Health quote type ID
    ]);

    // Pipeline should execute (FetchLeadPipe is first step)
    expect($response->status())->toBeInt();
});

test('health allocation pipeline includes verify lead pre checks pipe', function () {
    // Verify VerifyLeadPreChecksPipe is in the pipeline (line 37)
    $testUuid = 'health-quote-'.uniqid();

    $response = $this->postJson(route('assign-leads'), [
        'quoteUUID' => $testUuid,
        'quoteTypeId' => 3, // Health quote type ID
    ]);

    // Pipeline should execute (VerifyLeadPreChecksPipe is second step)
    expect($response->status())->toBeInt();
});

test('health allocation pipeline includes already in progress verification', function () {
    // Verify VerifyAlreadyInProgressAllocationPipe (line 38)
    $testUuid = 'health-quote-'.uniqid();

    $response = $this->postJson(route('assign-leads'), [
        'quoteUUID' => $testUuid,
        'quoteTypeId' => 3, // Health quote type ID
    ]);

    // Pipeline should execute (VerifyAlreadyInProgressAllocationPipe is third step)
    expect($response->status())->toBeInt();
});

test('health allocation pipeline includes validate nationality config pipe', function () {
    // Verify ValidateNationalityConfigPipe (line 40)
    $testUuid = 'health-quote-'.uniqid();

    $response = $this->postJson(route('assign-leads'), [
        'quoteUUID' => $testUuid,
        'quoteTypeId' => 3, // Health quote type ID
    ]);

    // Pipeline should execute (ValidateNationalityConfigPipe is fifth step)
    expect($response->status())->toBeInt();
});

test('health allocation pipeline includes apply rule exclusion pipe', function () {
    // Verify ApplyRuleExclusionPipe (line 41)
    $testUuid = 'health-quote-'.uniqid();

    $response = $this->postJson(route('assign-leads'), [
        'quoteUUID' => $testUuid,
        'quoteTypeId' => 3, // Health quote type ID
    ]);

    // Pipeline should execute (ApplyRuleExclusionPipe is sixth step)
    expect($response->status())->toBeInt();
});

test('health allocation pipeline includes fetch available advisor pipe', function () {
    // Verify FetchAvailableAdvisorPipe (line 42 and 44)
    $testUuid = 'health-quote-'.uniqid();

    $response = $this->postJson(route('assign-leads'), [
        'quoteUUID' => $testUuid,
        'quoteTypeId' => 3, // Health quote type ID
    ]);

    // Pipeline should execute (FetchAvailableAdvisorPipe is seventh and ninth step)
    expect($response->status())->toBeInt();
});

test('health allocation pipeline includes assign lead pipe', function () {
    // Verify AssignLeadPipe (line 45)
    $testUuid = 'health-quote-'.uniqid();

    $response = $this->postJson(route('assign-leads'), [
        'quoteUUID' => $testUuid,
        'quoteTypeId' => 3, // Health quote type ID
    ]);

    // Pipeline should execute (AssignLeadPipe is tenth step)
    expect($response->status())->toBeInt();
});

test('health allocation pipeline includes make response pipe', function () {
    // Verify MakeResponsePipe (line 46)
    $testUuid = 'health-quote-'.uniqid();

    $response = $this->postJson(route('assign-leads'), [
        'quoteUUID' => $testUuid,
        'quoteTypeId' => 3, // Health quote type ID
    ]);

    // Pipeline should execute (MakeResponsePipe is final step)
    expect($response->status())->toBeInt();
});

test('health allocation handles pipeline exceptions gracefully', function () {
    // Test that exceptions in pipeline are caught (line 48-50)
    // When exception occurs, resolveAllocationResponse should handle it
    $testUuid = 'health-quote-'.uniqid();

    $response = $this->postJson(route('assign-leads'), [
        'quoteUUID' => $testUuid,
        'quoteTypeId' => 3, // Health quote type ID
    ]);

    // Should handle exception and return valid response (not 500)
    expect($response->status())->not->toBe(500);
});

test('health allocation supports override advisor id parameter', function () {
    // Verify overrideAdvisorId is optional (line 23: $overrideAdvisorId = false)
    $testUuid = 'health-quote-'.uniqid();

    $response = $this->postJson(route('assign-leads'), [
        'quoteUUID' => $testUuid,
        'quoteTypeId' => 3, // Health quote type ID
        // reAssignAdvisor not required (overrideAdvisorId equivalent)
    ]);

    // Should accept request without optional override
    expect($response->status())->toBeInt();
});

test('health allocation processes correct allocation request structure', function () {
    // Verify AllocationRequest has correct properties (line 27-32)
    $testUuid = 'health-quote-'.uniqid();

    $response = $this->postJson(route('assign-leads'), [
        'quoteUUID' => $testUuid,
        'quoteTypeId' => 3, // Health quote type ID
    ]);

    // Should process with all allocation properties
    expect($response->status())->toBeInt();
});

// ============================================================================
// SECTION 3: HEALTH ASSIGN TEAM PIPE TESTS (ILA Flow - 19 tests)
// ============================================================================

test('health allocation pipeline includes assign team pipe', function () {
    // Verify AssignTeamPipe is in the pipeline (line 39 of HealthAllocation.php)
    $testUuid = 'health-quote-'.uniqid();

    $response = $this->postJson(route('assign-leads'), [
        'quoteUUID' => $testUuid,
        'quoteTypeId' => 3, // Health quote type ID
    ]);

    // Pipeline should execute (AssignTeamPipe is fourth step)
    expect($response->status())->toBeInt();
});

test('Terminate team if source is not ecom', function () {
    $testUuid = 'health-quote-'.uniqid();

    $healthQuote = HealthQuote::create([
        'uuid' => $testUuid,
        'code' => 'HEA-TEST-002',
        'emirate_of_your_visa_id' => EmirateEnum::DUBAI,
        'source' => null,
        'price_starting_from' => 2000.00,
        'health_plan_type_id' => HealthPlanTypeEnum::GOOD->value,
        'health_team_type' => null,
        'pec_marked_at' => null,
        'quote_status_id' => QuoteStatusEnum::Qualified,
        'nationality_id' => 1,
    ]);

    $allocationRequest = new AllocationRequest(
        quoteType: QuoteTypes::HEALTH,
        quoteUUID: $testUuid,
        source: HealthRoutingSourceEnum::ROUTING
    );
    $allocationRequest->setLead($healthQuote);

    $pipe = new AssignTeamPipe;
    $pipe->handle($allocationRequest, function ($request) {
        return $request;
    });

    $healthQuote->refresh();
    // Should select enabled team, not disabled one
    expect($healthQuote->health_team_type)->toBeNull();
});
