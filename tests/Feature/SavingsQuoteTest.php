<?php

use App\Enums\PaymentStatusEnum;
use App\Models\Payment;
use App\Models\PersonalQuote;
use App\Services\HttpRequestService;
use App\Services\Quotes\SavingsQuoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\Helpers\Savings\SavingsQuoteMockHelper;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    config([
        'constants.KEN_API_ENDPOINT' => 'http://api',
        'constants.KEN_API_USER' => 'test',
        'constants.KEN_API_PWD' => 'test',
        'constants.KEN_API_TOKEN' => 'test-token',
        'constants.KEN_API_TIMEOUT' => 30,
    ]);

    TestSchemaCreator::createMinimalSchema();

    $this->user = TestDataSeeder::createAdminUser();
    $this->actingAs($this->user);
});

afterEach(function () {
    $this->app->forgetInstance(SavingsQuoteService::class);
    $this->app->forgetInstance(HttpRequestService::class);
    Mockery::close();
});

test('can retrieve available plans for valid quote UUID', function () {
    $quote = SavingsQuoteMockHelper::createTestSavingsQuote();

    $mockHttpService = Mockery::mock(HttpRequestService::class);
    $this->app->singleton(HttpRequestService::class, fn () => $mockHttpService);

    $mockPlans = SavingsQuoteMockHelper::createPlansResponse(
        regular: [
            SavingsQuoteMockHelper::createMockPlanData(1),
            SavingsQuoteMockHelper::createMockPlanData(2),
        ],
        lumpsum: [SavingsQuoteMockHelper::createMockPlanData(3)]
    );

    $mockService = SavingsQuoteMockHelper::mockSavingsQuoteService($mockHttpService, $mockPlans);
    $this->app->instance(SavingsQuoteService::class, $mockService);

    $service = $this->app->make(SavingsQuoteService::class);
    $plans = $service->getAvailablePlans($quote->uuid);

    expect($plans)->not->toBeNull()
        ->and($plans)->toBeInstanceOf(stdClass::class)
        ->and(isset($plans->regular) || isset($plans->lumpsum))->toBeTrue();
});

test('retrieving available plans handles empty plan list gracefully', function () {
    $quote = SavingsQuoteMockHelper::createTestSavingsQuote();

    $mockHttpService = Mockery::mock(HttpRequestService::class);
    $this->app->singleton(HttpRequestService::class, fn () => $mockHttpService);

    $mockPlans = SavingsQuoteMockHelper::createPlansResponse(regular: [], lumpsum: []);
    $mockService = SavingsQuoteMockHelper::mockSavingsQuoteService($mockHttpService, $mockPlans);
    $this->app->instance(SavingsQuoteService::class, $mockService);

    $service = $this->app->make(SavingsQuoteService::class);
    $plans = $service->getAvailablePlans($quote->uuid);

    expect($plans)->not->toBeNull()
        ->and($plans)->toBeInstanceOf(stdClass::class);
});

test('retrieving available plans for non-existent quote handles API error', function () {
    $nonExistentUuid = 'non-existent-uuid-'.uniqid();

    $mockHttpService = Mockery::mock(HttpRequestService::class);
    $this->app->singleton(HttpRequestService::class, fn () => $mockHttpService);

    $mockService = SavingsQuoteMockHelper::mockSavingsQuoteService($mockHttpService, 'Quote not found');
    $this->app->instance(SavingsQuoteService::class, $mockService);

    $service = $this->app->make(SavingsQuoteService::class);
    $plans = $service->getAvailablePlans($nonExistentUuid);

    expect($plans)->toBeString()
        ->and($plans)->toContain('Quote not found');
});

test('retrieving available plans includes required fields', function () {
    $quote = SavingsQuoteMockHelper::createTestSavingsQuote();
    $mockPlan = SavingsQuoteMockHelper::createMockPlanData(1);

    $mockHttpService = Mockery::mock(HttpRequestService::class);
    $this->app->singleton(HttpRequestService::class, fn () => $mockHttpService);

    $mockPlans = SavingsQuoteMockHelper::createPlansResponse(regular: [$mockPlan]);
    $mockService = SavingsQuoteMockHelper::mockSavingsQuoteService($mockHttpService, $mockPlans);
    $this->app->instance(SavingsQuoteService::class, $mockService);

    $service = $this->app->make(SavingsQuoteService::class);
    $plans = $service->getAvailablePlans($quote->uuid);

    expect($plans)->not->toBeNull()
        ->and($plans)->toBeInstanceOf(stdClass::class);

    if (isset($plans->regular) && count($plans->regular) > 0) {
        $firstPlan = $plans->regular[0];
        expect($firstPlan->id)->toBe(1)
            ->and($firstPlan->name)->toBeString()
            ->and($firstPlan->providerCode)->toBeString();
    }
});

test('can get plan details for valid quote and plan ID', function () {
    $quote = SavingsQuoteMockHelper::createTestSavingsQuote();
    $planId = 1;
    $mockPlan = SavingsQuoteMockHelper::createMockPlanData($planId);

    $mockHttpService = Mockery::mock(HttpRequestService::class);
    $this->app->singleton(HttpRequestService::class, fn () => $mockHttpService);

    $mockPlans = SavingsQuoteMockHelper::createPlansResponse(regular: [$mockPlan]);
    $mockService = SavingsQuoteMockHelper::mockSavingsQuoteService($mockHttpService, $mockPlans);
    $this->app->instance(SavingsQuoteService::class, $mockService);

    $service = $this->app->make(SavingsQuoteService::class);
    $result = $service->getPlanDetails($quote->uuid, $planId);

    expect($result['error'])->toBeFalse()
        ->and($result['status'])->toBe(200)
        ->and($result['data'])->toBeArray()
        ->and($result['data']['id'])->toBe($planId)
        ->and($result['data']['name'])->toBeString()
        ->and($result['data']['providerCode'])->toBeString();
});

test('getting plan details extracts eligibility values correctly', function () {
    $quote = SavingsQuoteMockHelper::createTestSavingsQuote();
    $planId = 1;
    $mockPlan = SavingsQuoteMockHelper::createMockPlanData($planId, [
        'eligibilities' => [
            (object) ['code' => 'minimumInvestmentAmount', 'value' => '10000'],
            (object) ['code' => 'policyTerm', 'value' => '15'],
        ],
    ]);

    $mockHttpService = Mockery::mock(HttpRequestService::class);
    $this->app->singleton(HttpRequestService::class, fn () => $mockHttpService);

    $mockPlans = SavingsQuoteMockHelper::createPlansResponse(regular: [$mockPlan]);
    $mockService = SavingsQuoteMockHelper::mockSavingsQuoteService($mockHttpService, $mockPlans);
    $this->app->instance(SavingsQuoteService::class, $mockService);

    $service = $this->app->make(SavingsQuoteService::class);
    $result = $service->getPlanDetails($quote->uuid, $planId);

    expect($result['data']['minimumInvestment'])->toBe('10000')
        ->and($result['data']['policyTerm'])->toBe('15');
});

test('getting plan details includes fund details documents from API plan', function () {
    $quote = SavingsQuoteMockHelper::createTestSavingsQuote();
    $planId = 1;
    $mockPlan = SavingsQuoteMockHelper::createMockPlanData($planId, [
        'fundDetails' => [
            (object) ['id' => 1, 'text' => 'Fund factsheet', 'link' => 'https://example.com/fund.pdf'],
        ],
    ]);

    $mockHttpService = Mockery::mock(HttpRequestService::class);
    $this->app->singleton(HttpRequestService::class, fn () => $mockHttpService);

    $mockPlans = SavingsQuoteMockHelper::createPlansResponse(regular: [$mockPlan]);
    $mockService = SavingsQuoteMockHelper::mockSavingsQuoteService($mockHttpService, $mockPlans);
    $this->app->instance(SavingsQuoteService::class, $mockService);

    $service = $this->app->make(SavingsQuoteService::class);
    $result = $service->getPlanDetails($quote->uuid, $planId);

    expect($result['error'])->toBeFalse()
        ->and($result['data']['fundDetails'])->toHaveCount(1)
        ->and($result['data']['fundDetails'][0]->text)->toBe('Fund factsheet')
        ->and($result['data']['fundDetails'][0]->link)->toBe('https://example.com/fund.pdf');
});

test('getting plan details returns 404 for non-existent plan', function () {
    $quote = SavingsQuoteMockHelper::createTestSavingsQuote();
    $nonExistentPlanId = 999;

    $mockHttpService = Mockery::mock(HttpRequestService::class);
    $this->app->singleton(HttpRequestService::class, fn () => $mockHttpService);

    $mockPlans = SavingsQuoteMockHelper::createPlansResponse(
        regular: [SavingsQuoteMockHelper::createMockPlanData(1)]
    );
    $mockService = SavingsQuoteMockHelper::mockSavingsQuoteService($mockHttpService, $mockPlans);
    $this->app->instance(SavingsQuoteService::class, $mockService);

    $service = $this->app->make(SavingsQuoteService::class);
    $result = $service->getPlanDetails($quote->uuid, $nonExistentPlanId);

    expect($result['error'])->toBeTrue()
        ->and($result['status'])->toBe(404)
        ->and($result['message'])->toContain('Plan not found');
});

test('getting plan details returns error for non-existent quote', function () {
    $nonExistentUuid = 'non-existent-uuid-'.uniqid();

    $mockHttpService = Mockery::mock(HttpRequestService::class);
    $this->app->singleton(HttpRequestService::class, fn () => $mockHttpService);

    $mockService = SavingsQuoteMockHelper::mockSavingsQuoteService($mockHttpService, 'Quote not found');
    $this->app->instance(SavingsQuoteService::class, $mockService);

    $service = $this->app->make(SavingsQuoteService::class);
    $result = $service->getPlanDetails($nonExistentUuid, 1);

    expect($result['error'])->toBeTrue()
        ->and($result['status'])->toBe(404);
});

test('can create new plan with valid data', function () {
    $quote = SavingsQuoteMockHelper::createTestSavingsQuote();
    $mockHttpService = SavingsQuoteMockHelper::mockHttpRequestService(200);
    $this->app->singleton(HttpRequestService::class, fn () => $mockHttpService);

    $payload = [
        'quoteUID' => $quote->uuid,
        'update' => false,
        'plans' => [[
            'planId' => 1,
            'investmentAmount' => 10000,
            'currency' => 'AED',
            'currencyId' => 2,
            'paymentTerm' => 12,
            'tenure' => 10,
            'ror' => 5.5,
            'investmentFrequency' => 'Regular',
            'isDisabled' => false,
            'isManualUpdate' => true,
        ]],
    ];

    $service = $this->app->make(SavingsQuoteService::class);
    $response = $service->processSavingsPlan($payload, $quote->uuid);

    expect($response)->toBe(200);
});

test('plan creation validates required fields', function () {
    $quote = SavingsQuoteMockHelper::createTestSavingsQuote();

    $mockHttpService = Mockery::mock(HttpRequestService::class);
    $this->app->singleton(HttpRequestService::class, fn () => $mockHttpService);

    $mockService = Mockery::mock(SavingsQuoteService::class, [$mockHttpService])->makePartial();
    $mockService->shouldReceive('processSavingsPlan')->once()->andReturn(400);
    $this->app->instance(SavingsQuoteService::class, $mockService);

    $response = $this->postJson(route('savingsPlanManualProcess', $quote->uuid), [
        'update' => false,
        'plans' => [],
    ]);

    $response->assertStatus(400)
        ->assertJson(['message' => 'Failed to process savings plan']);
});

test('plan creation handles API errors gracefully', function () {
    $quote = SavingsQuoteMockHelper::createTestSavingsQuote();
    $mockHttpService = Mockery::mock(HttpRequestService::class);
    $mockHttpService->shouldReceive('processRequest')
        ->andReturn('API Error: Service unavailable');
    $this->app->singleton(HttpRequestService::class, fn () => $mockHttpService);

    $payload = [
        'quoteUID' => $quote->uuid,
        'update' => false,
        'plans' => [['planId' => 1, 'investmentAmount' => 10000]],
    ];

    $service = $this->app->make(SavingsQuoteService::class);
    $response = $service->processSavingsPlan($payload, $quote->uuid);

    expect($response)->toBeString()
        ->and($response)->toContain('API Error');
});

test('can update plan with valid data', function () {
    $quote = SavingsQuoteMockHelper::createTestSavingsQuote();
    $mockHttpService = SavingsQuoteMockHelper::mockHttpRequestService(200);
    $this->app->singleton(HttpRequestService::class, fn () => $mockHttpService);

    $payload = [
        'quoteUID' => $quote->uuid,
        'update' => true,
        'plans' => [[
            'planId' => 1,
            'actualPremium' => 15000,
            'isDisabled' => false,
            'isManualUpdate' => true,
            'insurerQuoteNo' => 'UPD-123',
        ]],
    ];

    $service = $this->app->make(SavingsQuoteService::class);
    $response = $service->processSavingsPlan($payload, $quote->uuid);

    expect($response)->toBe(200);
});

test('plan update checks permission based on payment status', function () {
    $quote = SavingsQuoteMockHelper::createTestSavingsQuote([
        'payment_status_id' => PaymentStatusEnum::CAPTURED,
    ]);

    Payment::create([
        'code' => $quote->code,
        'paymentable_id' => $quote->id,
        'paymentable_type' => PersonalQuote::class,
        'payment_status_id' => PaymentStatusEnum::CAPTURED,
        'captured_at' => now()->subDays(15),
    ]);

    $request = new Request([
        'quote_uuid' => $quote->uuid,
        'plan_id' => 1,
        'provider_name' => 'Test Provider',
    ]);

    $service = $this->app->make(SavingsQuoteService::class);
    $result = $service->isPlanModifyAllowed($request->all());

    expect($result)->toBeString()
        ->and($result)->toContain('not allowed');
});

test('handles API error responses correctly', function () {
    $quote = SavingsQuoteMockHelper::createTestSavingsQuote();

    $mockHttpService = Mockery::mock(HttpRequestService::class);
    $this->app->singleton(HttpRequestService::class, fn () => $mockHttpService);

    $mockService = SavingsQuoteMockHelper::mockSavingsQuoteService($mockHttpService, 'API timeout error');
    $this->app->instance(SavingsQuoteService::class, $mockService);

    $service = $this->app->make(SavingsQuoteService::class);
    $plans = $service->getQuotePlans($quote->uuid);

    expect($plans)->toBeString()
        ->and($plans)->toContain('timeout');
});

test('handles invalid quote UUID format', function () {
    $invalidUuid = 'invalid-uuid-format';

    $mockHttpService = Mockery::mock(HttpRequestService::class);
    $this->app->singleton(HttpRequestService::class, fn () => $mockHttpService);

    $mockService = SavingsQuoteMockHelper::mockSavingsQuoteService($mockHttpService, 'Quote not found');
    $this->app->instance(SavingsQuoteService::class, $mockService);

    $response = $this->getJson(route('savings_plan_details', [
        'quoteId' => $invalidUuid,
        'planId' => 1,
    ]));

    expect($response->status())->toBe(404);
});

test('handles missing required fields in plan update request', function () {
    $response = $this->post(route('savingsPlanUpdate'), []);

    $response->assertStatus(302)
        ->assertSessionHasErrors(['quote_uuid', 'plan_id', 'provider_name']);
});

test('fetchSavingsProviderPlan returns KEN JSON on success via KenService', function () {
    Http::fake([
        'http://api/fetch-savings-provider-plan' => Http::response(['lumpSumPayout' => 50000], 200),
    ]);

    $quote = SavingsQuoteMockHelper::createTestSavingsQuote();

    $payload = [
        'quoteUID' => $quote->uuid,
        'planId' => 1,
        'providerCode' => 'TEST',
        'isIndividualLoading' => true,
        'lang' => 'en',
        'planData' => [
            'investmentAmount' => 1000,
            'currency' => 'AED',
            'paymentTerm' => 1,
            'investmentFrequency' => 'Regular',
        ],
    ];

    $service = $this->app->make(SavingsQuoteService::class);
    $result = $service->fetchSavingsProviderPlan($payload);

    expect($result['success'])->toBeTrue()
        ->and($result['data'])->toMatchArray(['lumpSumPayout' => 50000]);

    Http::assertSent(function ($request) {
        return $request->url() === 'http://api/fetch-savings-provider-plan'
            && $request->hasHeader('Authorization')
            && $request->hasHeader('x-api-token');
    });
});

test('fetchSavingsProviderPlan maps KEN error response without success', function () {
    Http::fake([
        'http://api/fetch-savings-provider-plan' => Http::response(['message' => 'Invalid tenure'], 422),
    ]);

    $quote = SavingsQuoteMockHelper::createTestSavingsQuote();

    $payload = [
        'quoteUID' => $quote->uuid,
        'planId' => 1,
        'providerCode' => 'TEST',
        'isIndividualLoading' => true,
        'lang' => 'en',
        'planData' => [
            'investmentAmount' => 1000,
            'currency' => 'AED',
            'paymentTerm' => 1,
            'investmentFrequency' => 'Regular',
        ],
    ];

    $service = $this->app->make(SavingsQuoteService::class);
    $result = $service->fetchSavingsProviderPlan($payload);

    expect($result['success'])->toBeFalse()
        ->and($result['status'])->toBe(422)
        ->and($result['message'])->toBe('Invalid tenure');
});
