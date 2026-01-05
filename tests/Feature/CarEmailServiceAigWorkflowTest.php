<?php

use App\Enums\QuoteTypeId;
use App\Enums\WorkflowTypeEnum;
use App\Models\CarQuote;
use App\Models\QuoteFlowDetails;
use App\Services\EmailServices\CarEmailService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Helpers\CarAigWorkflowMockHelper;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    $adminUser = TestDataSeeder::createAdminUser([
        'email' => 'admin@example.com',
    ]);
    Auth::guard('web')->login($adminUser);

    // Prevent model event listeners from causing unrelated test failures.
    Event::fake();
});

afterEach(function () {
    Mockery::close();
});

test('triggers AIG workflow and sets aig_flow_executed_at (and creates flow details when Run-Id exists)', function () {
    $workflowUrl = 'https://example.test/bird/workflow';
    TestDataSeeder::seedBirdNbMotorWorkflowUrl($workflowUrl);

    $advisor = TestDataSeeder::createUser([
        'email' => 'advisor@example.com',
        'name' => 'Advisor User',
    ]);

    $quote = TestDataSeeder::createCarQuote([
        'advisor_id' => $advisor->id,
        'aig_flow_executed_at' => null,
    ]);

    CarAigWorkflowMockHelper::mockQuoteAdditionalDetailNoConsent();
    CarAigWorkflowMockHelper::mockBirdServiceTrigger(
        expectedUrl: $workflowUrl,
        runId: 'test-run-id',
        statusCode: 200,
        payloadAssert: function ($payload) use ($quote) {
            if (! is_object($payload)) {
                return false;
            }

            return ($payload->quoteUID ?? null) === $quote->uuid
                && ($payload->uuid ?? null) === $quote->uuid
                && ($payload->customerEmail ?? null) === $quote->email
                && ($payload->refID ?? null) === $quote->code
                && ($payload->advisorId ?? null) === $quote->advisor_id
                && ($payload->workflowType ?? null) === WorkflowTypeEnum::AIG_WORKFLOW
                && ($payload->whatsappConsent ?? null) === false;
        }
    );

    $response = app(CarEmailService::class)->sendAIGWorkflow($quote->fresh());

    expect($response)->not->toBeNull()
        ->and($response->status_code)->toBe(200);

    $quoteAfter = CarQuote::on('sqlite')->where('uuid', $quote->uuid)->firstOrFail();
    expect($quoteAfter->aig_flow_executed_at)->not->toBeNull();

    $flow = QuoteFlowDetails::query()
        ->where('quote_uuid', $quote->uuid)
        ->where('quote_type_id', QuoteTypeId::Car)
        ->first();
    expect($flow)->not->toBeNull()
        ->and($flow->flow_id)->toBe('test-run-id');
});

test('does not trigger workflow when already executed', function () {
    $workflowUrl = 'https://example.test/bird/workflow';
    TestDataSeeder::seedBirdNbMotorWorkflowUrl($workflowUrl);

    $advisor = TestDataSeeder::createUser([
        'email' => 'advisor3@example.com',
        'name' => 'Advisor User 3',
    ]);

    $quote = TestDataSeeder::createCarQuote([
        'advisor_id' => $advisor->id,
        'aig_flow_executed_at' => now(),
    ]);

    CarAigWorkflowMockHelper::mockQuoteAdditionalDetailNoConsent();

    // Ensure BirdService was not called.
    $birdMock = Mockery::mock(\App\Services\BirdService::class);
    $birdMock->shouldNotReceive('triggerWebHookRequest');
    app()->instance(\App\Services\BirdService::class, $birdMock);

    $response = app(CarEmailService::class)->sendAIGWorkflow($quote->fresh());
    expect($response)->toBeNull();
});

test('does not trigger workflow when workflow key missing', function () {
    // Ensure workflow key is absent
    DB::connection('sqlite')->table('application_storage')->where('key_name', \App\Enums\ApplicationStorageEnums::BIRD_NB_MOTOR_WORKFLOW)->delete();

    $advisor = TestDataSeeder::createUser([
        'email' => 'advisor4@example.com',
        'name' => 'Advisor User 4',
    ]);

    $quote = TestDataSeeder::createCarQuote([
        'advisor_id' => $advisor->id,
        'aig_flow_executed_at' => null,
    ]);

    CarAigWorkflowMockHelper::mockQuoteAdditionalDetailNoConsent();

    // Ensure BirdService was not called.
    $birdMock = Mockery::mock(\App\Services\BirdService::class);
    $birdMock->shouldNotReceive('triggerWebHookRequest');
    app()->instance(\App\Services\BirdService::class, $birdMock);

    $response = app(CarEmailService::class)->sendAIGWorkflow($quote->fresh());
    expect($response)->toBeNull();

    $quoteAfter = CarQuote::on('sqlite')->where('uuid', $quote->uuid)->firstOrFail();
    expect($quoteAfter->aig_flow_executed_at)->toBeNull();
});


