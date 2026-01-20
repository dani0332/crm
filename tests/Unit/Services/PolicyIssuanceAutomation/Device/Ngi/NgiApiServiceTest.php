<?php

declare(strict_types=1);

use App\Enums\NgiEnum;
use App\Facades\Ngi;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiApiService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiQuoteUpdaterService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiRequestBuilder;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiResponseHandler;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiValidationService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Database\Factories\DeviceQuoteFactory;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;

beforeEach(function () {
    // Create mocked dependencies for unit tests
    $this->requestBuilder = Mockery::mock(NgiRequestBuilder::class);
    $this->responseHandler = new NgiResponseHandler;
    $this->quoteUpdater = Mockery::mock(NgiQuoteUpdaterService::class);
    $this->validationService = Mockery::mock(NgiValidationService::class);

    $this->apiService = new NgiApiService(
        $this->requestBuilder,
        $this->responseHandler,
        $this->quoteUpdater,
        $this->validationService
    );
});

afterEach(function () {
    Mockery::close();
});

describe('createPolicyFromQuote', function () {
    test('returns success when API call succeeds', function () {
        $quote = DeviceQuoteFactory::makeMockWithRelations();
        $process = DeviceQuoteFactory::makeMockProcess();

        $this->requestBuilder->shouldReceive('buildCreatePolicyFromQuotePayload')
            ->once()
            ->andReturn(['quote_reference_number' => 'NGI-Q-123']);
        $this->requestBuilder->shouldReceive('buildCreatePolicyHeaders')
            ->once()
            ->andReturn(['Accept' => 'application/json']);

        // Mock the Ngi facade
        $responseData = (object) [
            'isSuccess' => true,
            'statusMessage' => 'Policy created successfully',
            'policy_no' => 'NGI-POL-123',
            'policy_start_dt' => '2024-01-15',
            'policy_end_dt' => '2025-01-15',
        ];

        $psr7Response = new Psr7Response(200, [], json_encode($responseData));
        $httpResponse = new Response($psr7Response);

        Ngi::shouldReceive('post')
            ->once()
            ->andReturn($httpResponse);
        Ngi::shouldReceive('getBaseUrl')
            ->andReturn('https://api.ngi.example.com');

        // Mock PolicyIssuanceService
        $policyIssuanceServiceMock = Mockery::mock(PolicyIssuanceService::class);
        $policyIssuanceServiceMock->shouldReceive('storePolicyIssuanceLog')->once();
        app()->instance(PolicyIssuanceService::class, $policyIssuanceServiceMock);

        // Mock quote updater
        $this->quoteUpdater->shouldReceive('updateQuoteFromCreatePolicyResponse')->once();

        $result = $this->apiService->createPolicyFromQuote($quote, $process);

        expect($result['status'])->toBeTrue()
            ->and($result['completed_step'])->toBe(NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE)
            ->and($result['data']->policy_no)->toBe('NGI-POL-123');
    });

    test('returns failure when API returns isSuccess false', function () {
        $quote = DeviceQuoteFactory::makeMockWithRelations();
        $process = DeviceQuoteFactory::makeMockProcess();

        $this->requestBuilder->shouldReceive('buildCreatePolicyFromQuotePayload')
            ->once()
            ->andReturn(['quote_reference_number' => 'NGI-Q-123']);
        $this->requestBuilder->shouldReceive('buildCreatePolicyHeaders')
            ->once()
            ->andReturn(['Accept' => 'application/json']);

        $responseData = (object) [
            'isSuccess' => false,
            'statusMessage' => 'Invalid quote reference',
            'errorCode' => 'ERR_INVALID_QUOTE',
        ];

        $psr7Response = new Psr7Response(200, [], json_encode($responseData));
        $httpResponse = new Response($psr7Response);

        Ngi::shouldReceive('post')->once()->andReturn($httpResponse);
        Ngi::shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');

        $policyIssuanceServiceMock = Mockery::mock(PolicyIssuanceService::class);
        $policyIssuanceServiceMock->shouldReceive('storePolicyIssuanceLog')->once();
        app()->instance(PolicyIssuanceService::class, $policyIssuanceServiceMock);

        $result = $this->apiService->createPolicyFromQuote($quote, $process);

        expect($result['status'])->toBeFalse()
            ->and($result['error'])->toBe('ERR_INVALID_QUOTE');
    });

    test('returns failure when API returns error code', function () {
        $quote = DeviceQuoteFactory::makeMockWithRelations();
        $process = DeviceQuoteFactory::makeMockProcess();

        $this->requestBuilder->shouldReceive('buildCreatePolicyFromQuotePayload')
            ->once()
            ->andReturn(['quote_reference_number' => 'NGI-Q-123']);
        $this->requestBuilder->shouldReceive('buildCreatePolicyHeaders')
            ->once()
            ->andReturn(['Accept' => 'application/json']);

        $responseData = (object) [
            'errorCode' => 'ERR_QUOTE_EXPIRED',
            'message' => 'Quote has expired',
        ];

        $psr7Response = new Psr7Response(200, [], json_encode($responseData));
        $httpResponse = new Response($psr7Response);

        Ngi::shouldReceive('post')->once()->andReturn($httpResponse);
        Ngi::shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');

        $policyIssuanceServiceMock = Mockery::mock(PolicyIssuanceService::class);
        $policyIssuanceServiceMock->shouldReceive('storePolicyIssuanceLog')->once();
        app()->instance(PolicyIssuanceService::class, $policyIssuanceServiceMock);

        $result = $this->apiService->createPolicyFromQuote($quote, $process);

        expect($result['status'])->toBeFalse()
            ->and($result['error'])->toBe('ERR_QUOTE_EXPIRED');
    });

    test('stores policy issuance log on success', function () {
        $quote = DeviceQuoteFactory::makeMockWithRelations();
        $process = DeviceQuoteFactory::makeMockProcess();

        $this->requestBuilder->shouldReceive('buildCreatePolicyFromQuotePayload')
            ->once()
            ->andReturn(['quote_reference_number' => 'NGI-Q-123']);
        $this->requestBuilder->shouldReceive('buildCreatePolicyHeaders')
            ->once()
            ->andReturn(['Accept' => 'application/json']);

        $responseData = (object) [
            'isSuccess' => true,
            'statusMessage' => 'Policy created',
            'policy_no' => 'NGI-POL-123',
            'policy_start_dt' => '2024-01-15',
            'policy_end_dt' => '2025-01-15',
        ];

        $psr7Response = new Psr7Response(200, [], json_encode($responseData));
        $httpResponse = new Response($psr7Response);

        Ngi::shouldReceive('post')->once()->andReturn($httpResponse);
        Ngi::shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');

        $policyIssuanceServiceMock = Mockery::mock(PolicyIssuanceService::class);
        $policyIssuanceServiceMock->shouldReceive('storePolicyIssuanceLog')
            ->once()
            ->with(
                $quote,
                Mockery::type('array'),
                Mockery::type('array'),
                Mockery::type('string'),
                NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
                \App\Enums\PolicyIssuanceEnum::SUCCESS_STATUS,
                $process
            );
        app()->instance(PolicyIssuanceService::class, $policyIssuanceServiceMock);

        $this->quoteUpdater->shouldReceive('updateQuoteFromCreatePolicyResponse')->once();

        $this->apiService->createPolicyFromQuote($quote, $process);
    });

    test('updates quote from API response on success', function () {
        $quote = DeviceQuoteFactory::makeMockWithRelations();
        $process = DeviceQuoteFactory::makeMockProcess();

        $this->requestBuilder->shouldReceive('buildCreatePolicyFromQuotePayload')
            ->once()
            ->andReturn(['quote_reference_number' => 'NGI-Q-123']);
        $this->requestBuilder->shouldReceive('buildCreatePolicyHeaders')
            ->once()
            ->andReturn(['Accept' => 'application/json']);

        $responseData = (object) [
            'isSuccess' => true,
            'statusMessage' => 'Policy created',
            'policy_no' => 'NGI-POL-123',
            'policy_start_dt' => '2024-01-15',
            'policy_end_dt' => '2025-01-15',
        ];

        $psr7Response = new Psr7Response(200, [], json_encode($responseData));
        $httpResponse = new Response($psr7Response);

        Ngi::shouldReceive('post')->once()->andReturn($httpResponse);
        Ngi::shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');

        $policyIssuanceServiceMock = Mockery::mock(PolicyIssuanceService::class);
        $policyIssuanceServiceMock->shouldReceive('storePolicyIssuanceLog')->once();
        app()->instance(PolicyIssuanceService::class, $policyIssuanceServiceMock);

        $this->quoteUpdater->shouldReceive('updateQuoteFromCreatePolicyResponse')
            ->once()
            ->with($quote, Mockery::on(function ($arg) {
                return $arg->policy_no === 'NGI-POL-123';
            }));

        $this->apiService->createPolicyFromQuote($quote, $process);
    });
});

describe('getPolicyDocuments', function () {
    test('returns success when API call succeeds', function () {
        $quote = DeviceQuoteFactory::makeMockWithRelations(['policy_number' => 'NGI-POL-123']);
        $process = DeviceQuoteFactory::makeMockProcess();

        $this->validationService->shouldReceive('validatePolicyNumberExists')
            ->once()
            ->with($quote)
            ->andReturn(['status' => true]);

        $responseData = (object) [
            'isSuccess' => true,
            'statusMessage' => 'Documents retrieved',
            'policy_no' => 'NGI-POL-123',
            'policy_certificate_url' => 'https://example.com/policy.pdf',
            'premium_inv_doc_url' => 'https://example.com/invoice.pdf',
            'commision_inv_doc_url' => 'https://example.com/commission.pdf',
        ];

        $psr7Response = new Psr7Response(200, [], json_encode($responseData));
        $httpResponse = new Response($psr7Response);

        Ngi::shouldReceive('get')->once()->andReturn($httpResponse);
        Ngi::shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');

        $policyIssuanceServiceMock = Mockery::mock(PolicyIssuanceService::class);
        $policyIssuanceServiceMock->shouldReceive('storePolicyIssuanceLog')->once();
        app()->instance(PolicyIssuanceService::class, $policyIssuanceServiceMock);

        $this->quoteUpdater->shouldReceive('updateQuoteFromPolicyDocumentsResponse')->once();
        $this->quoteUpdater->shouldReceive('updatePaymentFromPolicyDocumentsResponse')->once();

        $result = $this->apiService->getPolicyDocuments($quote, $process);

        expect($result['status'])->toBeTrue()
            ->and($result['completed_step'])->toBe(NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM);
    });

    test('returns failure when policy number validation fails', function () {
        $quote = DeviceQuoteFactory::makeMockWithRelations(['policy_number' => null]);
        $process = DeviceQuoteFactory::makeMockProcess();

        $this->validationService->shouldReceive('validatePolicyNumberExists')
            ->once()
            ->with($quote)
            ->andReturn([
                'status' => false,
                'error' => 'Policy number not found',
                'message' => 'Policy number not found',
            ]);

        $result = $this->apiService->getPolicyDocuments($quote, $process);

        expect($result['status'])->toBeFalse()
            ->and($result['error'])->toBe('Policy number not found');
    });

    test('returns failure when API call fails', function () {
        $quote = DeviceQuoteFactory::makeMockWithRelations(['policy_number' => 'NGI-POL-123']);
        $process = DeviceQuoteFactory::makeMockProcess();

        $this->validationService->shouldReceive('validatePolicyNumberExists')
            ->once()
            ->andReturn(['status' => true]);

        $responseData = (object) [
            'isSuccess' => false,
            'statusMessage' => 'Documents not ready',
            'errorCode' => 'ERR_DOCS_PENDING',
        ];

        $psr7Response = new Psr7Response(200, [], json_encode($responseData));
        $httpResponse = new Response($psr7Response);

        Ngi::shouldReceive('get')->once()->andReturn($httpResponse);
        Ngi::shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');

        $policyIssuanceServiceMock = Mockery::mock(PolicyIssuanceService::class);
        $policyIssuanceServiceMock->shouldReceive('storePolicyIssuanceLog')->once();
        app()->instance(PolicyIssuanceService::class, $policyIssuanceServiceMock);

        $result = $this->apiService->getPolicyDocuments($quote, $process);

        expect($result['status'])->toBeFalse()
            ->and($result['error'])->toBe('ERR_DOCS_PENDING');
    });

    test('updates quote and payment from API response on success', function () {
        $quote = DeviceQuoteFactory::makeMockWithRelations(['policy_number' => 'NGI-POL-123', 'code' => 'DEV-123']);
        $process = DeviceQuoteFactory::makeMockProcess();

        $this->validationService->shouldReceive('validatePolicyNumberExists')
            ->once()
            ->andReturn(['status' => true]);

        $responseData = (object) [
            'isSuccess' => true,
            'statusMessage' => 'Documents retrieved',
            'policy_no' => 'NGI-POL-123',
            'policy_certificate_url' => 'https://example.com/policy.pdf',
        ];

        $psr7Response = new Psr7Response(200, [], json_encode($responseData));
        $httpResponse = new Response($psr7Response);

        Ngi::shouldReceive('get')->once()->andReturn($httpResponse);
        Ngi::shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');

        $policyIssuanceServiceMock = Mockery::mock(PolicyIssuanceService::class);
        $policyIssuanceServiceMock->shouldReceive('storePolicyIssuanceLog')->once();
        app()->instance(PolicyIssuanceService::class, $policyIssuanceServiceMock);

        $this->quoteUpdater->shouldReceive('updateQuoteFromPolicyDocumentsResponse')
            ->once()
            ->with($quote, Mockery::on(fn ($arg) => $arg->policy_no === 'NGI-POL-123'));

        $this->quoteUpdater->shouldReceive('updatePaymentFromPolicyDocumentsResponse')
            ->once()
            ->with('DEV-123', Mockery::any());

        $this->apiService->getPolicyDocuments($quote, $process);
    });
});
