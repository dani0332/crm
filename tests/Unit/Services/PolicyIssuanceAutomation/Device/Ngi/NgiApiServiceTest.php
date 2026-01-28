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

// Global variables for shared instances (Pest compatible)
$sharedResponseHandler = null;
$sharedNgiFacadeMock = null;
$successPolicyResponse = null;
$errorPolicyResponse = null;
$successDocumentsResponse = null;
$errorDocumentsResponse = null;

beforeAll(function () {
    global $sharedResponseHandler, $sharedNgiFacadeMock, $successPolicyResponse, $errorPolicyResponse, $successDocumentsResponse, $errorDocumentsResponse;

    // Create shared stateless service instances once per test class
    $sharedResponseHandler = new NgiResponseHandler;

    // Create shared Ngi facade mock with default expectations
    $sharedNgiFacadeMock = Mockery::mock();
    $sharedNgiFacadeMock->shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');
    $sharedNgiFacadeMock->shouldReceive('post')->andReturnNull(); // Default behavior, can be overridden
    $sharedNgiFacadeMock->shouldReceive('get')->andReturnNull(); // Default behavior, can be overridden

    // Create reusable response objects
    initializeResponseObjects($successPolicyResponse, $errorPolicyResponse, $successDocumentsResponse, $errorDocumentsResponse);

    // Swap the facade with our shared mock
    \App\Facades\Ngi::swap($sharedNgiFacadeMock);
});

/**
 * Initialize reusable response objects to reduce memory allocation overhead
 */
function initializeResponseObjects(&$successPolicyResponse, &$errorPolicyResponse, &$successDocumentsResponse, &$errorDocumentsResponse): void
{
    // Success policy creation response
    $successPolicyData = (object) [
        'isSuccess' => true,
        'statusMessage' => 'Policy created successfully',
        'policy_no' => 'NGI-POL-123',
        'policy_start_dt' => '2024-01-15',
        'policy_end_dt' => '2025-01-15',
    ];
    $psr7Response = new Psr7Response(200, [], json_encode($successPolicyData));
    $successPolicyResponse = new Response($psr7Response);

    // Error policy response
    $errorPolicyData = (object) [
        'isSuccess' => false,
        'statusMessage' => 'Invalid quote reference',
        'errorCode' => 'ERR_INVALID_QUOTE',
    ];
    $psr7Response = new Psr7Response(200, [], json_encode($errorPolicyData));
    $errorPolicyResponse = new Response($psr7Response);

    // Success documents response
    $successDocumentsData = (object) [
        'isSuccess' => true,
        'statusMessage' => 'Documents retrieved',
        'policy_no' => 'NGI-POL-123',
        'policy_certificate_url' => 'https://example.com/policy.pdf',
        'premium_inv_doc_url' => 'https://example.com/invoice.pdf',
        'commision_inv_doc_url' => 'https://example.com/commission.pdf',
    ];
    $psr7Response = new Psr7Response(200, [], json_encode($successDocumentsData));
    $successDocumentsResponse = new Response($psr7Response);

    // Error documents response
    $errorDocumentsData = (object) [
        'isSuccess' => false,
        'statusMessage' => 'Documents not ready',
        'errorCode' => 'ERR_DOCS_PENDING',
    ];
    $psr7Response = new Psr7Response(200, [], json_encode($errorDocumentsData));
    $errorDocumentsResponse = new Response($psr7Response);
}


/**
 * Create a custom policy response with given data
 */
function createPolicyResponse(object $data): Response
{
    $psr7Response = new Psr7Response(200, [], json_encode($data));
    return new Response($psr7Response);
}

/**
 * Create a custom documents response with given data
 */
function createDocumentsResponse(object $data): Response
{
    $psr7Response = new Psr7Response(200, [], json_encode($data));
    return new Response($psr7Response);
}

afterAll(function () {
    global $sharedNgiFacadeMock, $successPolicyResponse, $errorPolicyResponse, $successDocumentsResponse, $errorDocumentsResponse;

    // Clean up shared facade mock
    if ($sharedNgiFacadeMock) {
        \App\Facades\Ngi::swap(null); // Remove the mock
    }

    // Clean up response objects (globals will be cleaned up automatically)
});

beforeEach(function () {
    global $sharedResponseHandler;

    // Create mocked dependencies for unit tests
    $this->requestBuilder = Mockery::mock(NgiRequestBuilder::class);
    $this->responseHandler = $sharedResponseHandler; // Reuse shared instance
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
    // Aggressive Mockery cleanup
    Mockery::close();
    Mockery::getContainer()->mockery_close();

    // Explicitly unset test properties to free memory (keep shared instances)
    unset($this->apiService, $this->requestBuilder, $this->quoteUpdater, $this->validationService);
    // Note: $this->responseHandler is a shared static instance, don't unset it

    // Clear service container bindings
    app()->forgetInstance(\App\Services\PolicyIssuanceAutomation\PolicyIssuanceService::class);

    // Reset Ngi facade expectations but keep the shared mock
    global $sharedNgiFacadeMock;
    if ($sharedNgiFacadeMock) {
        $sharedNgiFacadeMock->mockery_teardown();
        $sharedNgiFacadeMock->shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');
        $sharedNgiFacadeMock->shouldReceive('post')->andReturnNull();
        $sharedNgiFacadeMock->shouldReceive('get')->andReturnNull();
    }

    // Force garbage collection
    gc_collect_cycles();
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

        // Create success response for this test
        $responseData = (object) [
            'isSuccess' => true,
            'statusMessage' => 'Policy created successfully',
            'policy_no' => 'NGI-POL-123',
            'policy_start_dt' => '2024-01-15',
            'policy_end_dt' => '2025-01-15',
        ];
        $psr7Response = new Psr7Response(200, [], json_encode($responseData));
        $httpResponse = new Response($psr7Response);

        // Mock the facade for this specific call
        \App\Facades\Ngi::shouldReceive('post')->once()->andReturn($httpResponse);
        \App\Facades\Ngi::shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');

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

        // Create error response for this test
        $responseData = (object) [
            'isSuccess' => false,
            'statusMessage' => 'Invalid quote reference',
            'errorCode' => 'ERR_INVALID_QUOTE',
        ];
        $psr7Response = new Psr7Response(200, [], json_encode($responseData));
        $httpResponse = new Response($psr7Response);

        // Mock the facade for this specific call
        \App\Facades\Ngi::shouldReceive('post')->once()->andReturn($httpResponse);
        \App\Facades\Ngi::shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');

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

        $customResponseData = (object) [
            'errorCode' => 'ERR_QUOTE_EXPIRED',
            'message' => 'Quote has expired',
        ];
        $psr7Response = new Psr7Response(200, [], json_encode($customResponseData));
        $httpResponse = new Response($psr7Response);

        // Mock the facade for this specific call
        \App\Facades\Ngi::shouldReceive('post')->once()->andReturn($httpResponse);
        \App\Facades\Ngi::shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');

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

        // Create success response for this test
        $responseData = (object) [
            'isSuccess' => true,
            'statusMessage' => 'Policy created',
            'policy_no' => 'NGI-POL-123',
            'policy_start_dt' => '2024-01-15',
            'policy_end_dt' => '2025-01-15',
        ];
        $psr7Response = new Psr7Response(200, [], json_encode($responseData));
        $httpResponse = new Response($psr7Response);

        // Mock the facade for this specific call
        \App\Facades\Ngi::shouldReceive('post')->once()->andReturn($httpResponse);
        \App\Facades\Ngi::shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');

        $policyIssuanceServiceMock = Mockery::mock(PolicyIssuanceService::class);
        $policyIssuanceServiceMock->shouldReceive('storePolicyIssuanceLog')
            ->once()
            ->with(
                $quote,
                Mockery::type('array'),
                $httpResponse,
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

        // Create success response for this test
        $responseData = (object) [
            'isSuccess' => true,
            'statusMessage' => 'Policy created',
            'policy_no' => 'NGI-POL-123',
            'policy_start_dt' => '2024-01-15',
            'policy_end_dt' => '2025-01-15',
        ];
        $psr7Response = new Psr7Response(200, [], json_encode($responseData));
        $httpResponse = new Response($psr7Response);

        // Mock the facade for this specific call
        \App\Facades\Ngi::shouldReceive('post')->once()->andReturn($httpResponse);
        \App\Facades\Ngi::shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');

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

        // Create fresh response for this test
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

        // Mock the facade for this specific call
        \App\Facades\Ngi::shouldReceive('get')->once()->andReturn($httpResponse);
        \App\Facades\Ngi::shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');

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

        // Create error response for this test
        $responseData = (object) [
            'isSuccess' => false,
            'statusMessage' => 'Documents not ready',
            'errorCode' => 'ERR_DOCS_PENDING',
        ];
        $psr7Response = new Psr7Response(200, [], json_encode($responseData));
        $httpResponse = new Response($psr7Response);

        // Mock the facade for this specific call
        \App\Facades\Ngi::shouldReceive('get')->once()->andReturn($httpResponse);
        \App\Facades\Ngi::shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');

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

        $customDocumentsData = (object) [
            'isSuccess' => true,
            'statusMessage' => 'Documents retrieved',
            'policy_no' => 'NGI-POL-123',
            'policy_certificate_url' => 'https://example.com/policy.pdf',
        ];
        $psr7Response = new Psr7Response(200, [], json_encode($customDocumentsData));
        $httpResponse = new Response($psr7Response);

        // Mock the facade for this specific call
        \App\Facades\Ngi::shouldReceive('get')->once()->andReturn($httpResponse);
        \App\Facades\Ngi::shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');

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
