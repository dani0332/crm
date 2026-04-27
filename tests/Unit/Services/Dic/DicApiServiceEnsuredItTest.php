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
        ->andReturn(dicTestClientResponse([
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

    $service = new DicApiService(new DicResponseHandler, $builder);
    $result = $service->issuePolicy($quote, new PolicyIssuance);

    expect($result['status'])->toBeFalse()
        ->and($result['completed_step'])->toBe(PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY)
        ->and($result['message'])->toContain('already sold');
});

it('issuePolicy returns auth unavailable when HTTP client yields no response', function () {
    $httpClient = Mockery::mock(DicHttpClient::class);
    $httpClient->shouldReceive('buildUrl')->andReturn('https://unit-dic.test/products/buy/client');
    $httpClient->shouldReceive('authenticatedRequest')->andReturn(null);
    app()->instance(DicHttpClient::class, $httpClient);

    $builder = Mockery::mock(DicRequestBuilder::class);
    $builder->shouldReceive('buildIssuePolicyPayload')->andReturn(['policy_id' => '00000000-0000-4000-8000-0000000000aa']);

    $quote = new TravelQuote;
    $quote->code = 'UNIT-TQ-2';

    $service = new DicApiService(new DicResponseHandler, $builder);
    $result = $service->issuePolicy($quote, new PolicyIssuance);

    expect($result['status'])->toBeFalse()
        ->and($result['message'])->toBe(DicResponseHandler::MESSAGE_AUTH_TOKEN_UNAVAILABLE);
});

it('getPolicyDoc maps 401 AUTH_ERROR for certificate download path', function () {
    $policyId = '00000000-0000-4000-8000-0000000000bb';
    $expectedPath = 'policy-stores/'.$policyId.'/certificate:download';
    $expectedUrl = 'https://unit-dic.test/'.$expectedPath;

    $httpClient = Mockery::mock(DicHttpClient::class);
    $httpClient->shouldReceive('buildUrl')->once()->with($expectedPath)->andReturn($expectedUrl);
    $httpClient->shouldReceive('authenticatedRequest')->once()->with('GET', $expectedUrl)->andReturn(dicTestClientResponse([
        'code' => 'AUTH_ERROR',
        'message' => 'Token Expired',
    ], 401));
    app()->instance(DicHttpClient::class, $httpClient);

    $quote = new TravelQuote;
    $quote->code = 'UNIT-TQ-3';
    $quote->insurer_quote_number = $policyId;

    $service = new DicApiService(new DicResponseHandler, Mockery::mock(DicRequestBuilder::class));
    $result = $service->getPolicyDoc($quote, new PolicyIssuance);

    expect($result['status'])->toBeFalse()
        ->and($result['completed_step'])->toBe(PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC)
        ->and($result['message'])->toContain('token expired');
});

it('getBrokerInvoice maps INTERNAL_ERROR from invoice download path', function () {
    $policyId = '00000000-0000-4000-8000-0000000000cc';
    $expectedPath = 'policy-stores/invoice/'.$policyId.':download';
    $expectedUrl = 'https://unit-dic.test/'.$expectedPath;

    $httpClient = Mockery::mock(DicHttpClient::class);
    $httpClient->shouldReceive('buildUrl')->once()->with($expectedPath)->andReturn($expectedUrl);
    $httpClient->shouldReceive('authenticatedRequest')->once()->with('GET', $expectedUrl)->andReturn(dicTestClientResponse([
        'code' => 'INTERNAL_ERROR',
        'message' => 'Unable to get valid price',
    ], 500));
    app()->instance(DicHttpClient::class, $httpClient);

    $quote = new TravelQuote;
    $quote->code = 'UNIT-TQ-4';
    $quote->insurer_quote_number = $policyId;

    $service = new DicApiService(new DicResponseHandler, Mockery::mock(DicRequestBuilder::class));
    $result = $service->getBrokerInvoice($quote, new PolicyIssuance);

    expect($result['status'])->toBeFalse()
        ->and($result['completed_step'])->toBe(PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE)
        ->and($result['message'])->toContain('server error');
});
