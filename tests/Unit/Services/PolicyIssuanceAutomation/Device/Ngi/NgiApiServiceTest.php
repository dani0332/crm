<?php

declare(strict_types=1);

use App\Enums\NgiEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Facades\Ngi;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiApiService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiQuoteUpdaterService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiRequestBuilder;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiResponseHandler;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiValidationService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\PolicyIssuanceFailureEmailService;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;

// Global variables for shared instances (Pest compatible)
$sharedResponseHandler = null;
$sharedNgiFacadeMock = null;
$successPolicyResponse = null;
$errorPolicyResponse = null;
$expiredPolicyResponse = null;
$successDocumentsResponse = null;
$errorDocumentsResponse = null;
$sharedQuotePrototype = null;
$sharedProcessPrototype = null;

beforeAll(function () {
    global $sharedResponseHandler, $sharedNgiFacadeMock, $successPolicyResponse, $errorPolicyResponse, $expiredPolicyResponse, $successDocumentsResponse, $errorDocumentsResponse;

    // Create shared stateless service instances once per test class
    $sharedResponseHandler = new NgiResponseHandler;

    // Create shared Ngi facade mock with default expectations
    $sharedNgiFacadeMock = Mockery::mock();
    $sharedNgiFacadeMock->shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');
    $sharedNgiFacadeMock->shouldReceive('post')->andReturnNull(); // Default behavior, can be overridden
    $sharedNgiFacadeMock->shouldReceive('get')->andReturnNull(); // Default behavior, can be overridden

    // Create reusable response objects
    initializeResponseObjects($successPolicyResponse, $errorPolicyResponse, $expiredPolicyResponse, $successDocumentsResponse, $errorDocumentsResponse);

    // Pre-build shared prototypes to keep tests snappy
    getSharedQuote();
    getSharedProcess();

    // Swap the facade with our shared mock
    Ngi::swap($sharedNgiFacadeMock);
});

/**
 * Initialize reusable response objects to reduce memory allocation overhead
 */
function initializeResponseObjects(&$successPolicyResponse, &$errorPolicyResponse, &$expiredPolicyResponse, &$successDocumentsResponse, &$errorDocumentsResponse): void
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

    $expiredPolicyData = (object) [
        'isSuccess' => false,
        'statusMessage' => 'Quote has expired',
        'errorCode' => 'ERR_QUOTE_EXPIRED',
    ];
    $psr7Response = new Psr7Response(200, [], json_encode($expiredPolicyData));
    $expiredPolicyResponse = new Response($psr7Response);

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
 * Get a cloned quote prototype to avoid repeated Faker generation.
 *
 * @param  array<string, mixed>  $overrides
 */
function buildQuotePrototype(): object
{
    return new class
    {
        public int $id;
        public string $code;
        public string $uuid;
        public string $policy_start_date;
        public string $policy_expiry_date;
        public ?string $policy_number;
        public string $first_name;
        public string $last_name;
        public string $email;
        public string $mobile_no;
        public object $customer;
        public object $deviceQuote;
        public object $latestInsured;
        public object $latestPayment;

        public function __construct()
        {
            $defaults = [
                'id' => 1,
                'code' => 'DEV-00001',
                'uuid' => '00000000-0000-0000-0000-000000000001',
                'policy_start_date' => '2024-01-15',
                'policy_expiry_date' => '2025-01-15',
                'policy_number' => null,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
                'mobile_no' => '+971501234567',
            ];

            foreach ($defaults as $key => $value) {
                $this->{$key} = $value;
            }

            $this->customer = (object) [
                'id' => 1,
                'emirates_id_number' => '784-1234-12345678-1',
                'emirates_id_expiry_date' => '2026-01-15',
                'dob' => '1990-01-15',
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'mobile_no' => $this->mobile_no,
            ];

            $this->deviceQuote = (object) [
                'id' => 1,
                'imei' => '123456789012345',
            ];

            $this->latestInsured = (object) [
                'id' => 1,
                'id_type' => 'emiratesId',
                'id_number' => '784-1234-12345678-1',
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'mobile_no' => $this->mobile_no,
            ];

            $this->latestPayment = new class
            {
                public function paymentSplits()
                {
                    return new class
                    {
                        public function where($column, $value)
                        {
                            return new class
                            {
                                public function first()
                                {
                                    return null;
                                }
                            };
                        }
                    };
                }
            };
        }

        public function payments()
        {
            return new class($this->latestPayment)
            {
                private object $payment;

                public function __construct(object $payment)
                {
                    $this->payment = $payment;
                }

                public function mainLeadPayment()
                {
                    return $this;
                }

                public function first()
                {
                    return $this->payment;
                }
            };
        }

        public function save()
        {
            return true;
        }

        public function update()
        {
            return true;
        }

        public function getAttribute($attribute)
        {
            return $this->{$attribute} ?? null;
        }
    };
}

/**
 * Clone the quote prototype with optional overrides.
 *
 * @param  array<string, mixed>  $overrides
 */
function getSharedQuote(array $overrides = []): object
{
    global $sharedQuotePrototype;

    if (! $sharedQuotePrototype) {
        $sharedQuotePrototype = buildQuotePrototype();
    }

    if (empty($overrides)) {
        return $sharedQuotePrototype;
    }

    $quote = clone $sharedQuotePrototype;

    foreach ($overrides as $key => $value) {
        $quote->{$key} = $value;
    }

    return $quote;
}

function buildProcessPrototype(): object
{
    return (object) [
        'id' => 1,
        'status' => PolicyIssuanceEnum::PROCESSING_STATUS,
        'completed_step' => null,
    ];
}

/**
 * Get a cloned process prototype to keep tests lightweight.
 *
 * @param  array<string, mixed>  $overrides
 */
function getSharedProcess(array $overrides = []): object
{
    global $sharedProcessPrototype;

    if (! $sharedProcessPrototype) {
        $sharedProcessPrototype = buildProcessPrototype();
    }

    if (empty($overrides)) {
        return $sharedProcessPrototype;
    }

    $process = clone $sharedProcessPrototype;

    foreach ($overrides as $key => $value) {
        $process->{$key} = $value;
    }

    return $process;
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
        Ngi::swap(null); // Remove the mock
    }

    // Clean up response objects (globals will be cleaned up automatically)
});

beforeEach(function () {
    global $sharedResponseHandler;

    config()->set('logging.default', 'null');

    // Create mocked dependencies for unit tests
    $this->requestBuilder = Mockery::mock(NgiRequestBuilder::class);
    $this->responseHandler = $sharedResponseHandler; // Reuse shared instance
    $this->quoteUpdater = Mockery::mock(NgiQuoteUpdaterService::class);
    $this->quoteUpdater->shouldReceive('updateQuoteInsurerAndIssuanceStatus')
        ->andReturnNull()
        ->zeroOrMoreTimes()
        ->byDefault();
    $this->validationService = Mockery::mock(NgiValidationService::class);
    $this->failureEmailService = Mockery::mock(PolicyIssuanceFailureEmailService::class);
    $this->failureEmailService->shouldIgnoreMissing();
    $this->failureEmailService->shouldReceive('isPolicyIssuanceFailureEmail')->andReturnFalse()->byDefault();
    $this->failureEmailService->shouldReceive('isBookPolicyFailureEmail')->andReturnFalse()->byDefault();
    $this->failureEmailService->shouldReceive('isDocumentDownloadFailureEmail')->andReturnFalse()->byDefault();
    $this->failureEmailService->shouldReceive('isDocumentUploadFailureEmail')->andReturnFalse()->byDefault();

    $this->apiService = new NgiApiService(
        $this->requestBuilder,
        $this->responseHandler,
        $this->quoteUpdater,
        $this->validationService,
        $this->failureEmailService,
    );
});

afterEach(function () {
    // Aggressive Mockery cleanup
    Mockery::close();
    Mockery::getContainer()->mockery_close();

    // Explicitly unset test properties to free memory (keep shared instances)
    unset($this->apiService, $this->requestBuilder, $this->quoteUpdater, $this->validationService, $this->failureEmailService);
    // Note: $this->responseHandler is a shared static instance, don't unset it

    // Clear service container bindings
    app()->forgetInstance(PolicyIssuanceService::class);

    // Reset Ngi facade expectations but keep the shared mock
    global $sharedNgiFacadeMock;
    if ($sharedNgiFacadeMock) {
        $sharedNgiFacadeMock->mockery_teardown();
        $sharedNgiFacadeMock->shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');
        $sharedNgiFacadeMock->shouldReceive('post')->andReturnNull();
        $sharedNgiFacadeMock->shouldReceive('get')->andReturnNull();
    }
    gc_collect_cycles();
});

describe('createPolicyFromQuote', function () {
    test('returns failure when API returns isSuccess false', function () {
        $quote = getSharedQuote();
        $process = getSharedProcess();

        $this->requestBuilder->shouldReceive('buildCreatePolicyFromQuotePayload')
            ->once()
            ->andReturn(['quote_reference_number' => 'NGI-Q-123']);
        $this->requestBuilder->shouldReceive('buildCreatePolicyHeaders')
            ->once()
            ->andReturn(['Accept' => 'application/json']);

        global $errorPolicyResponse;

        $responseHandlerMock = Mockery::mock(NgiResponseHandler::class);
        $responseHandlerMock->shouldReceive('buildStepResponse')
            ->once()
            ->andReturn([
                'status' => false,
                'completed_step' => NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
                'message' => null,
                'error' => null,
                'data' => null,
            ]);
        $responseHandlerMock->shouldReceive('parseHttpResponse')
            ->once()
            ->andReturn([
                'status' => false,
                'error' => 'ERR_INVALID_QUOTE',
                'message' => 'Invalid quote reference',
                'data' => null,
                'completed_step' => null,
            ]);

        $this->apiService = new NgiApiService(
            $this->requestBuilder,
            $responseHandlerMock,
            $this->quoteUpdater,
            $this->validationService,
            $this->failureEmailService,
        );

        // Mock the facade for this specific call
        Ngi::shouldReceive('post')->once()->andReturn($errorPolicyResponse);
        Ngi::shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');

        $policyIssuanceServiceMock = Mockery::mock(PolicyIssuanceService::class);
        $policyIssuanceServiceMock->shouldReceive('storePolicyIssuanceLog')->once();
        app()->instance(PolicyIssuanceService::class, $policyIssuanceServiceMock);

        $result = $this->apiService->createPolicyFromQuote($quote, $process);

        expect($result['status'])->toBeFalse()
            ->and($result['error'])->toBe('ERR_INVALID_QUOTE');
    });

    test('stores policy issuance log on success', function () {
        $quote = getSharedQuote();
        $process = getSharedProcess();

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
        Ngi::shouldReceive('post')->once()->andReturn($httpResponse);
        Ngi::shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');

        $policyIssuanceServiceMock = Mockery::mock(PolicyIssuanceService::class);
        $policyIssuanceServiceMock->shouldReceive('storePolicyIssuanceLog')
            ->once()
            ->with(
                $quote,
                Mockery::type('array'),
                $httpResponse,
                Mockery::type('string'),
                NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
                PolicyIssuanceEnum::SUCCESS_STATUS,
                $process
            );
        app()->instance(PolicyIssuanceService::class, $policyIssuanceServiceMock);

        $this->quoteUpdater->shouldReceive('updateQuoteFromCreatePolicyResponse')->once();

        $this->apiService->createPolicyFromQuote($quote, $process);
    });

    test('updates quote from API response on success', function () {
        $quote = getSharedQuote();
        $process = getSharedProcess();

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

    test('returns success when API call succeeds', function () {
        $quote = getSharedQuote();
        $process = getSharedProcess();
        global $successPolicyResponse;

        $this->requestBuilder->shouldReceive('buildCreatePolicyFromQuotePayload')
            ->once()
            ->andReturn(['quote_reference_number' => 'NGI-Q-123']);
        $this->requestBuilder->shouldReceive('buildCreatePolicyHeaders')
            ->once()
            ->andReturn(['Accept' => 'application/json']);

        $httpResponse = $successPolicyResponse;

        // Mock the facade for this specific call
        Ngi::shouldReceive('post')->once()->andReturn($httpResponse);
        Ngi::shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');

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

    test('returns failure when the quote email is marked as fake policy issuance', function () {
        $quote = getSharedQuote(['email' => 'ngi-policy-issuance-failure@myalfred.fake']);
        $process = getSharedProcess();
        global $successPolicyResponse;

        $this->requestBuilder->shouldReceive('buildCreatePolicyFromQuotePayload')
            ->once()
            ->andReturn(['quote_reference_number' => 'NGI-Q-123']);
        $this->requestBuilder->shouldReceive('buildCreatePolicyHeaders')
            ->once()
            ->andReturn(['Accept' => 'application/json']);

        $httpResponse = $successPolicyResponse;

        Ngi::shouldReceive('post')->once()->andReturn($httpResponse);
        Ngi::shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');

        $policyIssuanceServiceMock = Mockery::mock(PolicyIssuanceService::class);
        $policyIssuanceServiceMock->shouldReceive('storePolicyIssuanceLog')->once();
        app()->instance(PolicyIssuanceService::class, $policyIssuanceServiceMock);

        $this->quoteUpdater->shouldNotReceive('updateQuoteFromCreatePolicyResponse');

        $this->failureEmailService
            ->shouldReceive('isPolicyIssuanceFailureEmail')
            ->once()
            ->with($quote->email)
            ->andReturnTrue();

        $result = $this->apiService->createPolicyFromQuote($quote, $process);

        expect($result['status'])->toBeFalse()
            ->and($result['message'])->toContain('Policy created successfully');
    });

    test('returns failure when API returns error code', function () {
        $quote = getSharedQuote();
        $process = getSharedProcess();

        $this->requestBuilder->shouldReceive('buildCreatePolicyFromQuotePayload')
            ->once()
            ->andReturn(['quote_reference_number' => 'NGI-Q-123']);
        $this->requestBuilder->shouldReceive('buildCreatePolicyHeaders')
            ->once()
            ->andReturn(['Accept' => 'application/json']);

        global $expiredPolicyResponse;

        // Mock the facade for this specific call
        Ngi::shouldReceive('post')->once()->andReturn($expiredPolicyResponse);
        Ngi::shouldReceive('getBaseUrl')->andReturn('https://api.ngi.example.com');

        $policyIssuanceServiceMock = Mockery::mock(PolicyIssuanceService::class);
        $policyIssuanceServiceMock->shouldReceive('storePolicyIssuanceLog')->once();
        app()->instance(PolicyIssuanceService::class, $policyIssuanceServiceMock);

        $result = $this->apiService->createPolicyFromQuote($quote, $process);

        expect($result['status'])->toBeFalse()
            ->and($result['error'])->toBe('ERR_QUOTE_EXPIRED');
    });
});

describe('getPolicyDocuments', function () {
    test('returns success when API call succeeds', function () {
        $quote = getSharedQuote(['policy_number' => 'NGI-POL-123']);
        $process = getSharedProcess();

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

    test('returns failure when the quote email is marked as fake document download', function () {
        $quote = getSharedQuote([
            'policy_number' => 'NGI-POL-123',
            'email' => 'ngi-doc-download-failure@myalfred.fake',
        ]);
        $process = getSharedProcess();

        $this->validationService->shouldReceive('validatePolicyNumberExists')
            ->once()
            ->with($quote)
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

        $this->quoteUpdater->shouldNotReceive('updateQuoteFromPolicyDocumentsResponse');
        $this->quoteUpdater->shouldNotReceive('updatePaymentFromPolicyDocumentsResponse');

        $this->failureEmailService
            ->shouldReceive('isDocumentDownloadFailureEmail')
            ->once()
            ->with($quote->email)
            ->andReturnTrue();

        $result = $this->apiService->getPolicyDocuments($quote, $process);

        expect($result['status'])->toBeFalse()
            ->and($result['message'])->toBe('Documents retrieved');
    });

    test('returns failure when policy number validation fails', function () {
        $quote = getSharedQuote(['policy_number' => null]);
        $process = getSharedProcess();

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
        $quote = getSharedQuote(['policy_number' => 'NGI-POL-123']);
        $process = getSharedProcess();

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
        $quote = getSharedQuote(['policy_number' => 'NGI-POL-123', 'code' => 'DEV-123']);
        $process = getSharedProcess();

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
