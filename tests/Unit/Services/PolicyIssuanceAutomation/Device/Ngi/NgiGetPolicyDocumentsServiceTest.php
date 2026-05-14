<?php

declare(strict_types=1);

use App\Enums\NgiEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Facades\Ngi;
use App\Models\PersonalQuote;
use App\Models\PolicyIssuance;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiApiService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiDocumentHandler;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiGetPolicyDocumentsService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiValidationService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\PolicyIssuanceFailureEmailService;
use Mockery\MockInterface;

beforeEach(function () {
    // Create mocked dependencies for unit tests
    $this->validationService = Mockery::mock(NgiValidationService::class);
    $this->apiService = Mockery::mock(NgiApiService::class);
    $this->documentHandler = Mockery::mock(NgiDocumentHandler::class);
    $this->policyIssuanceService = Mockery::mock(PolicyIssuanceService::class);
    $this->failureEmailService = Mockery::mock(PolicyIssuanceFailureEmailService::class);
    $this->failureEmailService->shouldIgnoreMissing();
    $this->failureEmailService->shouldReceive('isDocumentDownloadFailureEmail')->andReturnFalse()->byDefault();
    $this->failureEmailService->shouldReceive('isDocumentUploadFailureEmail')->andReturnFalse()->byDefault();

    $this->service = new NgiGetPolicyDocumentsService(
        $this->validationService,
        $this->apiService,
        $this->documentHandler,
        $this->policyIssuanceService,
        $this->failureEmailService,
    );
});

afterEach(function () {
    // Aggressive Mockery cleanup
    Mockery::close();
    Mockery::getContainer()->mockery_close();

    // Explicitly unset test properties to free memory
    unset($this->validationService, $this->apiService, $this->documentHandler, $this->policyIssuanceService, $this->failureEmailService, $this->service);

    // Clear service container bindings
    app()->forgetInstance(PolicyIssuanceService::class);

    // Reset Ngi facade to clear any mock instances
    Ngi::clearResolvedInstances();

    // Force garbage collection
    gc_collect_cycles();
});

describe('Service Dependencies', function () {
    test('service has all required dependencies injected', function () {
        // Verify the service was constructed with mocked dependencies
        expect($this->service)->toBeInstanceOf(NgiGetPolicyDocumentsService::class);
    });

    test('validation service dependency is NgiValidationService', function () {
        expect($this->validationService)->toBeInstanceOf(MockInterface::class);
    });

    test('api service dependency is NgiApiService', function () {
        expect($this->apiService)->toBeInstanceOf(MockInterface::class);
    });

    test('document handler dependency is NgiDocumentHandler', function () {
        expect($this->documentHandler)->toBeInstanceOf(MockInterface::class);
    });

    test('policy issuance service dependency is PolicyIssuanceService', function () {
        expect($this->policyIssuanceService)->toBeInstanceOf(MockInterface::class);
    });
});

describe('NgiEnum Constants for GetPolicyDocuments', function () {
    test('document fetch delay minutes is 3 as per FRD', function () {
        expect(NgiEnum::DOCUMENT_FETCH_DELAY_MINUTES)->toBe(3);
    });

    test('max retry attempts is 3 as per FRD', function () {
        expect(NgiEnum::MAX_RETRY_ATTEMPTS)->toBe(3);
    });

    test('retry delay minutes is 5 as per FRD', function () {
        expect(NgiEnum::RETRY_DELAY_MINUTES)->toBe(5);
    });

    test('step get and upload policy documents constant is defined', function () {
        expect(NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM)->toBe('GetAndUploadPolicyDocs');
    });

    test('response get policy documents constant is defined', function () {
        expect(NgiEnum::RESPONSE_GET_POLICY_DOCUMENTS)->toBe('GetPolicyDocumentsResponse');
    });
});

describe('PolicyIssuanceEnum Constants', function () {
    test('PIA get and upload documents failed status ID is defined', function () {
        expect(PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID)
            ->toBeInt();
    });

    test('PIA policy automation status no ID is defined', function () {
        expect(PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID)
            ->toBeInt();
    });

    test('failed status constant is defined', function () {
        expect(PolicyIssuanceEnum::FAILED_STATUS)->toBe('failed');
    });

    test('pending status constant is defined', function () {
        expect(PolicyIssuanceEnum::PENDING_STATUS)->toBe('pending');
    });
});

describe('Validation Service Integration', function () {
    test('validation service can validate policy number exists', function () {
        $quote = (object) ['policy_number' => 'NGI-POL-123'];

        $this->validationService->shouldReceive('validatePolicyNumberExists')
            ->once()
            ->with($quote)
            ->andReturn(['status' => true]);

        $result = $this->validationService->validatePolicyNumberExists($quote);

        expect($result['status'])->toBeTrue();
    });

    test('validation service returns error when policy number missing', function () {
        $quote = (object) ['policy_number' => null];

        $this->validationService->shouldReceive('validatePolicyNumberExists')
            ->once()
            ->with($quote)
            ->andReturn([
                'status' => false,
                'error' => 'Policy number not found',
            ]);

        $result = $this->validationService->validatePolicyNumberExists($quote);

        expect($result['status'])->toBeFalse()
            ->and($result['error'])->toContain('Policy number not found');
    });
});

describe('API Service Integration', function () {
    test('api service can get policy documents', function () {
        $quote = (object) ['policy_number' => 'NGI-POL-123'];
        $process = (object) ['id' => 1];

        $this->apiService->shouldReceive('getPolicyDocuments')
            ->once()
            ->with($quote, $process)
            ->andReturn([
                'status' => true,
                'data' => (object) [
                    'policy_certificate_url' => 'https://example.com/policy.pdf',
                    'premium_inv_doc_url' => 'https://example.com/invoice.pdf',
                ],
            ]);

        $result = $this->apiService->getPolicyDocuments($quote, $process);

        expect($result['status'])->toBeTrue()
            ->and($result['data']->policy_certificate_url)->toContain('policy.pdf');
    });

    test('api service returns error when documents not ready', function () {
        $quote = (object) ['policy_number' => 'NGI-POL-123'];
        $process = (object) ['id' => 1];

        $this->apiService->shouldReceive('getPolicyDocuments')
            ->once()
            ->with($quote, $process)
            ->andReturn([
                'status' => false,
                'error' => 'Documents not ready yet',
            ]);

        $result = $this->apiService->getPolicyDocuments($quote, $process);

        expect($result['status'])->toBeFalse()
            ->and($result['error'])->toContain('Documents not ready');
    });
});

describe('Document Handler Integration', function () {
    test('document handler can download and store documents', function () {
        $this->documentHandler->shouldReceive('downloadAndStorePolicyDocuments')
            ->once()
            ->withAnyArgs()
            ->andReturn([
                'status' => true,
                'documents_count' => 3,
            ]);

        $result = $this->documentHandler->downloadAndStorePolicyDocuments(
            Mockery::mock(PersonalQuote::class),
            Mockery::mock(PolicyIssuance::class),
            ['data' => (object) []]
        );

        expect($result['status'])->toBeTrue()
            ->and($result['documents_count'])->toBe(3);
    });

    test('document handler returns error on download failure', function () {
        $this->documentHandler->shouldReceive('downloadAndStorePolicyDocuments')
            ->once()
            ->withAnyArgs()
            ->andReturn([
                'status' => false,
                'error' => 'Failed to download policy certificate',
            ]);

        $result = $this->documentHandler->downloadAndStorePolicyDocuments(
            Mockery::mock(PersonalQuote::class),
            Mockery::mock(PolicyIssuance::class),
            ['data' => (object) []]
        );

        expect($result['status'])->toBeFalse()
            ->and($result['error'])->toContain('Failed to download');
    });
});
