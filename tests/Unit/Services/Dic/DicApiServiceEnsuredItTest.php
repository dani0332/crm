<?php

declare(strict_types=1);

use App\Enums\PolicyIssuanceEnum;
use App\Models\PolicyIssuance;
use App\Models\TravelQuote;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicApiService;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicHttpClient;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicRequestBuilder;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicResponseHandler;
use Tests\Helpers\DicTestHelper;

beforeEach(function () {
    config([
        'constants.DIC_API_BASE_URL' => 'https://unit-dic.test',
        'constants.DIC_API_TIMEOUT' => 10,
    ]);

    $policyIssuanceService = Mockery::mock(PolicyIssuanceService::class);
    $policyIssuanceService->shouldReceive('storePolicyIssuanceLog')->zeroOrMoreTimes();
    app()->instance(PolicyIssuanceService::class, $policyIssuanceService);
});

afterEach(function () {
    Mockery::close();
});

it('issuePolicy returns invalid payload without HTTP when policy_id is empty string', function () {
    $httpClient = Mockery::mock(DicHttpClient::class);
    $httpClient->shouldReceive('buildUrl')
        ->once()
        ->with('products/buy/client')
        ->andReturn('https://unit-dic.test/products/buy/client');
    $httpClient->shouldNotReceive('authenticatedRequest');
    app()->instance(DicHttpClient::class, $httpClient);

    $policyIssuanceService = Mockery::mock(PolicyIssuanceService::class);
    $policyIssuanceService
        ->shouldReceive('storePolicyIssuanceLog')
        ->once()
        ->with(
            Mockery::type(TravelQuote::class),
            Mockery::on(fn (array $p): bool => array_key_exists('policy_id', $p) && $p['policy_id'] === ''),
            Mockery::type('array'),
            'https://unit-dic.test/products/buy/client',
            PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
            PolicyIssuanceEnum::FAILED_STATUS,
            Mockery::type(PolicyIssuance::class),
        );
    app()->instance(PolicyIssuanceService::class, $policyIssuanceService);

    $builder = Mockery::mock(DicRequestBuilder::class);
    $builder->shouldReceive('buildIssuePolicyPayload')
        ->once()
        ->andReturn([
            'policy_id' => '',
            'payment_details' => ['Transaction_id' => null],
        ]);

    $quote = new TravelQuote;
    $quote->code = 'UNIT-TQ-empty-policy-id';
    $quote->insurer_quote_number = '';

    $service = new DicApiService(new DicResponseHandler, $builder, app(PolicyIssuanceService::class));
    $result = $service->issuePolicy($quote, new PolicyIssuance);

    expect($result['status'])->toBeFalse()
        ->and($result['completed_step'])->toBe(PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY)
        ->and($result['error'])->toContain('policy_id');
});

it('issuePolicy returns mapped EnsuredIT error for already sold policy', function () {
    $httpClient = Mockery::mock(DicHttpClient::class);
    $httpClient->shouldReceive('buildUrl')
        ->once()
        ->with('products/buy/client')
        ->andReturn('https://unit-dic.test/products/buy/client');
    $httpClient->shouldReceive('authenticatedRequest')
        ->once()
        ->with('POST', 'https://unit-dic.test/products/buy/client', [
            'policy_id' => '00000000-0000-4000-8000-0000000000aa',
        ])
        ->andReturn(DicTestHelper::clientResponse([
            'code' => 'VALIDATION_ERROR',
            'message' => 'Policy already sold',
            'policyStatus' => 'SOLD',
        ], 400));
    app()->instance(DicHttpClient::class, $httpClient);

    $builder = Mockery::mock(DicRequestBuilder::class);
    $builder->shouldReceive('buildIssuePolicyPayload')
        ->once()
        ->andReturn(['policy_id' => '00000000-0000-4000-8000-0000000000aa']);

    $quote = new TravelQuote;
    $quote->code = 'UNIT-TQ-1';
    $quote->insurer_quote_number = '00000000-0000-4000-8000-0000000000aa';

    $service = new DicApiService(new DicResponseHandler, $builder, app(PolicyIssuanceService::class));
    $result = $service->issuePolicy($quote, new PolicyIssuance);

    expect($result['status'])->toBeFalse()
        ->and($result['completed_step'])->toBe(PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY)
        ->and($result['message'])->toContain('already sold');
});

it('issuePolicy persists a failed log when HTTP client yields no response', function () {
    $policyIssuanceService = Mockery::mock(PolicyIssuanceService::class);
    $policyIssuanceService
        ->shouldReceive('storePolicyIssuanceLog')
        ->once()
        ->with(
            Mockery::type(TravelQuote::class),
            Mockery::type('array'),
            Mockery::type('array'),
            Mockery::type('string'),
            PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
            PolicyIssuanceEnum::FAILED_STATUS,
            Mockery::type(PolicyIssuance::class),
        );
    app()->instance(PolicyIssuanceService::class, $policyIssuanceService);

    $httpClient = Mockery::mock(DicHttpClient::class);
    $httpClient->shouldReceive('buildUrl')->andReturn('https://unit-dic.test/products/buy/client');
    $httpClient->shouldReceive('authenticatedRequest')->andReturn(null);
    app()->instance(DicHttpClient::class, $httpClient);

    $builder = Mockery::mock(DicRequestBuilder::class);
    $builder->shouldReceive('buildIssuePolicyPayload')->andReturn(['policy_id' => '00000000-0000-4000-8000-0000000000aa']);

    $quote = new TravelQuote;
    $quote->code = 'UNIT-TQ-2';

    $service = new DicApiService(new DicResponseHandler, $builder, app(PolicyIssuanceService::class));
    $result = $service->issuePolicy($quote, new PolicyIssuance);

    expect($result['status'])->toBeFalse()
        ->and($result['message'])->toBe(DicResponseHandler::MESSAGE_AUTH_TOKEN_UNAVAILABLE);
});

it('issuePolicy persists failed issuance log when HTTP succeeds but JSON body is not an array', function () {
    $policyIssuanceService = Mockery::mock(PolicyIssuanceService::class);
    $policyIssuanceService
        ->shouldReceive('storePolicyIssuanceLog')
        ->once()
        ->with(
            Mockery::type(TravelQuote::class),
            Mockery::type('array'),
            [],
            Mockery::type('string'),
            PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
            PolicyIssuanceEnum::FAILED_STATUS,
            Mockery::type(PolicyIssuance::class),
        );
    app()->instance(PolicyIssuanceService::class, $policyIssuanceService);

    $httpClient = Mockery::mock(DicHttpClient::class);
    $httpClient->shouldReceive('buildUrl')->andReturn('https://unit-dic.test/products/buy/client');
    $httpClient->shouldReceive('authenticatedRequest')->andReturn(DicTestHelper::clientResponse('"not-an-array"', 200));
    app()->instance(DicHttpClient::class, $httpClient);

    $builder = Mockery::mock(DicRequestBuilder::class);
    $builder->shouldReceive('buildIssuePolicyPayload')->andReturn(['policy_id' => '00000000-0000-4000-8000-0000000000aa']);

    $quote = new TravelQuote;
    $quote->code = 'UNIT-TQ-non-array';
    $quote->insurer_quote_number = '00000000-0000-4000-8000-0000000000aa';

    $service = new DicApiService(new DicResponseHandler, $builder, app(PolicyIssuanceService::class));
    $result = $service->issuePolicy($quote, new PolicyIssuance);

    expect($result['status'])->toBeFalse();
});

it('getPolicyDoc maps 401 AUTH_ERROR for certificate download path', function () {
    $policyId = '00000000-0000-4000-8000-0000000000bb';
    $expectedPath = 'policy-stores/'.$policyId.'/certificate:download';
    $expectedUrl = 'https://unit-dic.test/'.$expectedPath;

    $httpClient = Mockery::mock(DicHttpClient::class);
    $httpClient->shouldReceive('buildUrl')->once()->with($expectedPath)->andReturn($expectedUrl);
    $httpClient->shouldReceive('authenticatedRequest')->once()->with('GET', $expectedUrl)->andReturn(DicTestHelper::clientResponse([
        'code' => 'AUTH_ERROR',
        'message' => 'Token Expired',
    ], 401));
    app()->instance(DicHttpClient::class, $httpClient);

    $quote = new TravelQuote;
    $quote->code = 'UNIT-TQ-3';
    $quote->insurer_quote_number = $policyId;

    $service = new DicApiService(new DicResponseHandler, Mockery::mock(DicRequestBuilder::class), app(PolicyIssuanceService::class));
    $result = $service->getPolicyDoc($quote, new PolicyIssuance);

    expect($result['status'])->toBeFalse()
        ->and($result['completed_step'])->toBe(PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC)
        ->and($result['message'])->toContain('token expired');
});

it('getPolicyDoc returns failure without HTTP when insurer_quote_number is missing', function () {
    $policyIssuanceService = Mockery::mock(PolicyIssuanceService::class);
    $policyIssuanceService
        ->shouldReceive('storePolicyIssuanceLog')
        ->once()
        ->with(
            Mockery::type(TravelQuote::class),
            [],
            Mockery::type('array'),
            'dic-get-policy-doc/missing-insurer-quote-number',
            PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC,
            PolicyIssuanceEnum::FAILED_STATUS,
            Mockery::type(PolicyIssuance::class),
        );
    app()->instance(PolicyIssuanceService::class, $policyIssuanceService);

    $quote = new TravelQuote;
    $quote->code = 'UNIT-TQ-no-policy-id';
    $quote->insurer_quote_number = null;

    $service = new DicApiService(
        new DicResponseHandler,
        Mockery::mock(DicRequestBuilder::class),
        app(PolicyIssuanceService::class),
    );
    $result = $service->getPolicyDoc($quote, new PolicyIssuance);

    expect($result['status'])->toBeFalse()
        ->and($result['completed_step'])->toBe(PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC)
        ->and($result['error'])->toContain('insurer_quote_number');
});

it('getBrokerInvoice maps INTERNAL_ERROR from invoice download path', function () {
    $policyId = '00000000-0000-4000-8000-0000000000cc';
    $expectedPath = 'policy-stores/invoice/'.$policyId.':download';
    $expectedUrl = 'https://unit-dic.test/'.$expectedPath;

    $httpClient = Mockery::mock(DicHttpClient::class);
    $httpClient->shouldReceive('buildUrl')->once()->with($expectedPath)->andReturn($expectedUrl);
    $httpClient->shouldReceive('authenticatedRequest')->once()->with('GET', $expectedUrl)->andReturn(DicTestHelper::clientResponse([
        'code' => 'INTERNAL_ERROR',
        'message' => 'Unable to get valid price',
    ], 500));
    app()->instance(DicHttpClient::class, $httpClient);

    $quote = new TravelQuote;
    $quote->code = 'UNIT-TQ-4';
    $quote->insurer_quote_number = $policyId;

    $service = new DicApiService(new DicResponseHandler, Mockery::mock(DicRequestBuilder::class), app(PolicyIssuanceService::class));
    $result = $service->getBrokerInvoice($quote, new PolicyIssuance);

    expect($result['status'])->toBeFalse()
        ->and($result['completed_step'])->toBe(PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE)
        ->and($result['message'])->toContain('server error');
});
