<?php

namespace Tests\Unit\Services\PolicyIssuanceAutomation\Cyber;

use App\Enums\AwnicEnum;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicApiService;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicDocumentHandler;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicQuoteUpdaterService;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicRequestBuilder;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicResponseHandler;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicValidationService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;
use Mockery;
use Tests\Support\FakeAwnicQuote;
use Tests\Support\FakePolicyIssuanceProcess;
use Tests\Support\FakePolicyIssuanceService;
use Tests\TestCase;

class AwnicApiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app()->instance(PolicyIssuanceService::class, new FakePolicyIssuanceService());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_issue_policy_updates_quote_data_on_success(): void
    {
        $quote = new FakeAwnicQuote();
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
        $client->shouldReceive('post')->once()->andReturn($this->makeHttpResponse($responsePayload));
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

        $this->assertTrue($result['status']);
        $this->assertSame(AwnicEnum::STEP_ISSUE_POLICY, $result['completed_step']);
        $this->assertEquals($responseBodyObject, $result['data']);
    }

    public function test_issue_policy_returns_failure_on_error_response(): void
    {
        $quote = new FakeAwnicQuote();
        $process = new FakePolicyIssuanceProcess($quote);

        $requestBuilder = Mockery::mock(AwnicRequestBuilder::class);
        $requestBuilder->shouldReceive('buildIssuePolicyPayload')->once()->andReturn([]);
        $requestBuilder->shouldReceive('buildIssuePolicyHeaders')->once()->andReturn([]);

        $client = Mockery::mock(\App\Services\PolicyIssuanceAutomation\Cyber\AwnicHttpClient::class);
        $client->shouldReceive('post')->once()->andReturn($this->makeHttpResponse(['isSuccess' => 'N', 'errorList' => ['ERR']]));
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

        $this->assertFalse($result['status']);
        $this->assertEquals(['ERR'], $result['error']);
    }

    public function test_upload_documents_skips_when_validation_fails(): void
    {
        $quote = new FakeAwnicQuote();
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

        $this->assertFalse($result['status']);
        $this->assertSame('Required documents not uploaded', $result['error']);
    }

    private function makeHttpResponse(array $payload, int $status = 200): Response
    {
        return new Response(new Psr7Response($status, [], json_encode($payload)));
    }
}

