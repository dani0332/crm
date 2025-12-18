<?php

use App\Enums\AwnicEnum;
use App\Models\PersonalQuote;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicApiService;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicDocumentHandler;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicQuoteUpdaterService;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicRequestBuilder;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicResponseHandler;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicValidationService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;
use Tests\Helpers\TestSchemaCreator;
use Tests\Support\FakePolicyIssuanceProcess;
use Tests\Support\FakePolicyIssuanceService;

beforeEach(function () {
    TestSchemaCreator::createCyberSchema();
    app()->instance(PolicyIssuanceService::class, new FakePolicyIssuanceService());
});

afterEach(function () {
    Mockery::close();
});

function makeHttpResponse(array $payload, int $status = 200): Response
{
    return new Response(new Psr7Response($status, [], json_encode($payload)));
}

it('issues policy and updates quote data on success', function () {
    $quote = PersonalQuote::factory()->withCyberDependencies()->create();
    $process = new FakePolicyIssuanceProcess($quote);

    $payload = ['payload' => true];
    $headers = ['X-Test' => 'foo'];

    $requestBuilder = Mockery::mock(AwnicRequestBuilder::class);
    $requestBuilder->shouldReceive('buildIssuePolicyPayload')->once()->andReturn($payload);
    $requestBuilder->shouldReceive('buildIssuePolicyHeaders')->once()->andReturn($headers);

    $responsePayload = [
        'isSuccess' => 'Y',
        'policyInfo' => [
            'policyNo' => 'POL-123',
            'policyStartDate' => '2024-01-01',
            'policyEndDate' => '2025-01-01',
            'premiumAmount' => 100,
            'prmVatAmt' => 5,
            'prmPayableAmt' => 105,
        ],
    ];
    $responseBodyObject = json_decode(json_encode($responsePayload));

    $client = Mockery::mock(\App\Services\PolicyIssuanceAutomation\Cyber\AwnicHttpClient::class);
    $client->shouldReceive('post')->once()->andReturn(makeHttpResponse($responsePayload));
    $client->shouldReceive('getBaseUrl')->andReturn('https://awni.test');
    app()->instance('AwnicHttpClient', $client);

    $quoteUpdater = Mockery::mock(AwnicQuoteUpdaterService::class);
    $quoteUpdater->shouldReceive('updateQuoteFromIssuePolicyResponse')->once();
    $quoteUpdater->shouldReceive('updatePaymentFromIssuePolicyResponse')->once();

    $service = new AwnicApiService(
        $requestBuilder,
        new AwnicResponseHandler(),
        Mockery::mock(AwnicDocumentHandler::class),
        $quoteUpdater,
        Mockery::mock(AwnicValidationService::class)
    );

    $result = $service->issuePolicy($quote, $process);

    expect($result['status'])->toBeTrue()
        ->and($result['completed_step'])->toBe(AwnicEnum::STEP_ISSUE_POLICY)
        ->and($result['data'])->toEqual($responseBodyObject);
});

it('returns failure when issue policy API responds with error', function () {
    $quote = PersonalQuote::factory()->withCyberDependencies()->create();
    $process = new FakePolicyIssuanceProcess($quote);

    $requestBuilder = Mockery::mock(AwnicRequestBuilder::class);
    $requestBuilder->shouldReceive('buildIssuePolicyPayload')->once()->andReturn([]);
    $requestBuilder->shouldReceive('buildIssuePolicyHeaders')->once()->andReturn([]);

    $client = Mockery::mock(\App\Services\PolicyIssuanceAutomation\Cyber\AwnicHttpClient::class);
    $client->shouldReceive('post')->once()->andReturn(makeHttpResponse(['isSuccess' => 'N', 'errorList' => ['ERR']]));
    $client->shouldReceive('getBaseUrl')->andReturn('https://awni.test');
    app()->instance('AwnicHttpClient', $client);

    $quoteUpdater = Mockery::mock(AwnicQuoteUpdaterService::class);
    $quoteUpdater->shouldNotReceive('updateQuoteFromIssuePolicyResponse');

    $service = new AwnicApiService(
        $requestBuilder,
        new AwnicResponseHandler(),
        Mockery::mock(AwnicDocumentHandler::class),
        $quoteUpdater,
        Mockery::mock(AwnicValidationService::class)
    );

    $result = $service->issuePolicy($quote, $process);

    expect($result['status'])->toBeFalse()
        ->and($result['error'])->toEqual(['ERR']);
});

it('skips upload documents call when validation fails', function () {
    $quote = PersonalQuote::factory()->withCyberDependencies()->create();
    $process = new FakePolicyIssuanceProcess($quote);

    $validation = Mockery::mock(AwnicValidationService::class);
    $validation->shouldReceive('validateUploadDocuments')->andReturn([
        'status' => false,
        'error' => 'Required documents not uploaded',
        'message' => 'Required documents not uploaded',
    ]);

    $documentHandler = Mockery::mock(AwnicDocumentHandler::class);
    $documentHandler->shouldReceive('getDocumentByType')->andReturn(null);

    $service = new AwnicApiService(
        Mockery::mock(AwnicRequestBuilder::class),
        new AwnicResponseHandler(),
        $documentHandler,
        Mockery::mock(AwnicQuoteUpdaterService::class),
        $validation
    );

    $result = $service->uploadDocuments($quote, $process);

    expect($result['status'])->toBeFalse()
        ->and($result['error'])->toBe('Required documents not uploaded');
});

