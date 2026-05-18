<?php

declare(strict_types=1);

use App\Enums\AdnicEnum;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicApiService;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicDocumentHandler;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicQuoteUpdaterService;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicRequestBuilder;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicResponseHandler;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicValidationService;

beforeEach(function () {
    $this->requestBuilderMock = Mockery::mock(AdnicRequestBuilder::class);
    $this->responseHandlerMock = Mockery::mock(AdnicResponseHandler::class);
    $this->documentHandlerMock = Mockery::mock(AdnicDocumentHandler::class);
    $this->quoteUpdaterMock = Mockery::mock(AdnicQuoteUpdaterService::class);
    $this->validationServiceMock = Mockery::mock(AdnicValidationService::class);

    $this->service = new AdnicApiService(
        $this->requestBuilderMock,
        $this->responseHandlerMock,
        $this->documentHandlerMock,
        $this->quoteUpdaterMock,
        $this->validationServiceMock
    );
});

afterEach(function () {
    Mockery::close();
});

// CRITICAL TEST: Service initialization
test('api service initializes with all dependencies', function () {
    expect($this->service)->toBeInstanceOf(AdnicApiService::class);
});

// CRITICAL TEST: Request builder is properly injected
test('api service has request builder dependency', function () {
    $reflection = new ReflectionClass($this->service);
    $property = $reflection->getProperty('requestBuilder');
    $property->setAccessible(true);

    expect($property->getValue($this->service))->toBeInstanceOf(AdnicRequestBuilder::class);
});

// CRITICAL TEST: Response handler is properly injected
test('api service has response handler dependency', function () {
    $reflection = new ReflectionClass($this->service);
    $property = $reflection->getProperty('responseHandler');
    $property->setAccessible(true);

    expect($property->getValue($this->service))->toBeInstanceOf(AdnicResponseHandler::class);
});

// CRITICAL TEST: Document handler is properly injected
test('api service has document handler dependency', function () {
    $reflection = new ReflectionClass($this->service);
    $property = $reflection->getProperty('documentHandler');
    $property->setAccessible(true);

    expect($property->getValue($this->service))->toBeInstanceOf(AdnicDocumentHandler::class);
});

// CRITICAL TEST: Quote updater is properly injected
test('api service has quote updater dependency', function () {
    $reflection = new ReflectionClass($this->service);
    $property = $reflection->getProperty('quoteUpdater');
    $property->setAccessible(true);

    expect($property->getValue($this->service))->toBeInstanceOf(AdnicQuoteUpdaterService::class);
});

test('uploadPolicyDocumentsToIMCRM returns error when PolicyDocumentInfo is missing', function () {
    $quote = (object) ['uuid' => 'test-quote-uuid'];

    $log = new stdClass;
    $log->response = json_encode(['data' => (object) []]);

    $logsQuery = new class($log)
    {
        public function __construct(private object $log) {}

        public function where(array $conditions): self
        {
            return $this;
        }

        public function latest(): self
        {
            return $this;
        }

        public function first(): object
        {
            return $this->log;
        }
    };

    $process = new class($logsQuery)
    {
        public function __construct(private object $logsQuery) {}

        public int $id = 42;

        public function policyIssuanceLogs(): object
        {
            return $this->logsQuery;
        }
    };

    $this->responseHandlerMock->shouldReceive('buildStepResponse')
        ->with(AdnicEnum::STEP_UPLOAD_POLICY_DOCS)
        ->andReturn([
            'status' => false,
            'completed_step' => AdnicEnum::STEP_UPLOAD_POLICY_DOCS,
            'message' => null,
            'error' => null,
            'data' => null,
        ]);

    $result = $this->service->uploadPolicyDocumentsToIMCRM($quote, $process);

    expect($result['status'])->toBeFalse()
        ->and($result['error'])->toBe('Policy document list not found in policy issue response');
});
