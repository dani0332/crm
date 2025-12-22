<?php

declare(strict_types=1);

use App\Enums\DeviceFailureTypeEnum;
use App\Enums\NgiEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Services\DeviceFailureEmailService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiApiService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiBookPolicyService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiGetPolicyDocumentsJob;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiStepExecutor;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    // Create mocked dependencies for unit tests
    $this->apiService = Mockery::mock(NgiApiService::class);
    $this->bookPolicyService = Mockery::mock(NgiBookPolicyService::class);

    $this->stepExecutor = new NgiStepExecutor(
        $this->apiService,
        $this->bookPolicyService
    );
});

afterEach(function () {
    Mockery::close();
});

describe('executeCreatePolicyFromQuoteStep', function () {
    test('returns success response when API call succeeds', function () {
        $quote = createMockQuoteForStepExecutor();
        $process = createMockProcessForStepExecutor();

        $this->apiService->shouldReceive('createPolicyFromQuote')
            ->once()
            ->with($quote, $process)
            ->andReturn([
                'status' => true,
                'message' => 'Policy created successfully',
                'completed_step' => NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
                'data' => (object) ['policy_no' => 'NGI-POL-123'],
            ]);

        $result = $this->stepExecutor->executeCreatePolicyFromQuoteStep($quote, $process);

        expect($result['status'])->toBeTrue()
            ->and($result['completed_step'])->toBe(NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE)
            ->and($result['data']->policy_no)->toBe('NGI-POL-123');
    });

    test('returns failure response when API call fails', function () {
        $quote = createMockQuoteForStepExecutor();
        $process = createMockProcessForStepExecutor();

        $this->apiService->shouldReceive('createPolicyFromQuote')
            ->once()
            ->with($quote, $process)
            ->andReturn([
                'status' => false,
                'error' => 'Invalid quote number',
                'message' => 'Policy creation failed',
            ]);

        // Mock PolicyIssuanceService
        $policyIssuanceServiceMock = Mockery::mock(PolicyIssuanceService::class);
        $policyIssuanceServiceMock->shouldReceive('updateAPIIssuanceAndInsurerStatus')
            ->once();
        app()->instance(PolicyIssuanceService::class, $policyIssuanceServiceMock);

        // Mock DeviceFailureEmailService
        $emailServiceMock = Mockery::mock(DeviceFailureEmailService::class);
        $emailServiceMock->shouldReceive('sendFailureEmail')
            ->once()
            ->with($quote->id, DeviceFailureTypeEnum::ISSUE_POLICY);
        app()->instance(DeviceFailureEmailService::class, $emailServiceMock);

        $result = $this->stepExecutor->executeCreatePolicyFromQuoteStep($quote, $process);

        expect($result['status'])->toBeFalse()
            ->and($result['error'])->toBe('Invalid quote number');
    });

    test('triggers failure email when API call fails', function () {
        $quote = createMockQuoteForStepExecutor();
        $process = createMockProcessForStepExecutor();

        $this->apiService->shouldReceive('createPolicyFromQuote')
            ->once()
            ->andReturn([
                'status' => false,
                'error' => 'API Error',
            ]);

        $policyIssuanceServiceMock = Mockery::mock(PolicyIssuanceService::class);
        $policyIssuanceServiceMock->shouldReceive('updateAPIIssuanceAndInsurerStatus')->once();
        app()->instance(PolicyIssuanceService::class, $policyIssuanceServiceMock);

        $emailServiceMock = Mockery::mock(DeviceFailureEmailService::class);
        $emailServiceMock->shouldReceive('sendFailureEmail')
            ->once()
            ->with($quote->id, DeviceFailureTypeEnum::ISSUE_POLICY);
        app()->instance(DeviceFailureEmailService::class, $emailServiceMock);

        $this->stepExecutor->executeCreatePolicyFromQuoteStep($quote, $process);

        // Assertion is in the mock expectation
    });

    test('updates insurer API status when API call fails', function () {
        $quote = createMockQuoteForStepExecutor();
        $process = createMockProcessForStepExecutor();

        $this->apiService->shouldReceive('createPolicyFromQuote')
            ->once()
            ->andReturn([
                'status' => false,
                'error' => 'API Error',
            ]);

        $policyIssuanceServiceMock = Mockery::mock(PolicyIssuanceService::class);
        $policyIssuanceServiceMock->shouldReceive('updateAPIIssuanceAndInsurerStatus')
            ->once()
            ->with(
                $quote,
                \App\Enums\QuoteTypes::DEVICE->value,
                PolicyIssuanceEnum::PIA_POLICY_ISSUANCE_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID,
                'Policy Creation'
            );
        app()->instance(PolicyIssuanceService::class, $policyIssuanceServiceMock);

        $emailServiceMock = Mockery::mock(DeviceFailureEmailService::class);
        $emailServiceMock->shouldReceive('sendFailureEmail')->once();
        app()->instance(DeviceFailureEmailService::class, $emailServiceMock);

        $this->stepExecutor->executeCreatePolicyFromQuoteStep($quote, $process);

        // Assertion is in the mock expectation
    });
});

describe('executeGetPolicyDocumentsAndUploadToIMCRMStep', function () {
    test('dispatches job with delay and returns documents_pending response', function () {
        Queue::fake();

        $quote = createMockQuoteForStepExecutor(['policy_number' => 'NGI-POL-123']);
        $process = createMockProcessForStepExecutor();

        $result = $this->stepExecutor->executeGetPolicyDocumentsAndUploadToIMCRMStep($quote, $process);

        expect($result['status'])->toBeTrue()
            ->and($result['documents_pending'])->toBeTrue()
            ->and($result['completed_step'])->toBe(NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE)
            ->and($result['message'])->toContain('dispatched');

        Queue::assertPushed(NgiGetPolicyDocumentsJob::class);
    });

    test('includes delay minutes in response message', function () {
        Queue::fake();

        $quote = createMockQuoteForStepExecutor(['policy_number' => 'NGI-POL-123']);
        $process = createMockProcessForStepExecutor();

        $result = $this->stepExecutor->executeGetPolicyDocumentsAndUploadToIMCRMStep($quote, $process);

        $delayMinutes = NgiEnum::DOCUMENT_FETCH_DELAY_MINUTES;
        expect($result['message'])->toContain((string) $delayMinutes.'-minute delay');
    });
});

describe('executeBookPolicyStep', function () {
    test('returns success response when book policy succeeds', function () {
        $quote = createMockQuoteForStepExecutor(['policy_number' => 'NGI-POL-123']);
        $process = createMockProcessForStepExecutor();

        $this->bookPolicyService->shouldReceive('bookPolicy')
            ->once()
            ->with($quote, $process)
            ->andReturn([
                'status' => true,
                'message' => 'Booking process started!',
                'completed_step' => NgiEnum::STEP_BOOK_POLICY,
            ]);

        $result = $this->stepExecutor->executeBookPolicyStep($quote, $process);

        expect($result['status'])->toBeTrue()
            ->and($result['completed_step'])->toBe(NgiEnum::STEP_BOOK_POLICY);
    });

    test('returns failure response when book policy fails', function () {
        $quote = createMockQuoteForStepExecutor(['policy_number' => 'NGI-POL-123']);
        $process = createMockProcessForStepExecutor();

        $this->bookPolicyService->shouldReceive('bookPolicy')
            ->once()
            ->with($quote, $process)
            ->andReturn([
                'status' => false,
                'error' => 'Sage API failed',
                'message' => 'Book policy failed',
            ]);

        // Mock PolicyIssuanceService
        $policyIssuanceServiceMock = Mockery::mock(PolicyIssuanceService::class);
        $policyIssuanceServiceMock->shouldReceive('updateAPIIssuanceAndInsurerStatus')->once();
        app()->instance(PolicyIssuanceService::class, $policyIssuanceServiceMock);

        // Mock DeviceFailureEmailService
        $emailServiceMock = Mockery::mock(DeviceFailureEmailService::class);
        $emailServiceMock->shouldReceive('sendFailureEmail')
            ->once()
            ->with($quote->id, DeviceFailureTypeEnum::BOOK_POLICY);
        app()->instance(DeviceFailureEmailService::class, $emailServiceMock);

        $result = $this->stepExecutor->executeBookPolicyStep($quote, $process);

        expect($result['status'])->toBeFalse()
            ->and($result['error'])->toBe('Sage API failed');
    });

    test('triggers failure email when book policy fails', function () {
        $quote = createMockQuoteForStepExecutor(['policy_number' => 'NGI-POL-123']);
        $process = createMockProcessForStepExecutor();

        $this->bookPolicyService->shouldReceive('bookPolicy')
            ->once()
            ->andReturn([
                'status' => false,
                'error' => 'Booking failed',
            ]);

        $policyIssuanceServiceMock = Mockery::mock(PolicyIssuanceService::class);
        $policyIssuanceServiceMock->shouldReceive('updateAPIIssuanceAndInsurerStatus')->once();
        app()->instance(PolicyIssuanceService::class, $policyIssuanceServiceMock);

        $emailServiceMock = Mockery::mock(DeviceFailureEmailService::class);
        $emailServiceMock->shouldReceive('sendFailureEmail')
            ->once()
            ->with($quote->id, DeviceFailureTypeEnum::BOOK_POLICY);
        app()->instance(DeviceFailureEmailService::class, $emailServiceMock);

        $this->stepExecutor->executeBookPolicyStep($quote, $process);

        // Assertion is in the mock expectation
    });

    test('updates insurer API status when book policy fails', function () {
        $quote = createMockQuoteForStepExecutor(['policy_number' => 'NGI-POL-123']);
        $process = createMockProcessForStepExecutor();

        $this->bookPolicyService->shouldReceive('bookPolicy')
            ->once()
            ->andReturn([
                'status' => false,
                'error' => 'Booking failed',
            ]);

        $policyIssuanceServiceMock = Mockery::mock(PolicyIssuanceService::class);
        $policyIssuanceServiceMock->shouldReceive('updateAPIIssuanceAndInsurerStatus')
            ->once()
            ->with(
                $quote,
                \App\Enums\QuoteTypes::DEVICE->value,
                PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID,
                'Send And Book Policy'
            );
        app()->instance(PolicyIssuanceService::class, $policyIssuanceServiceMock);

        $emailServiceMock = Mockery::mock(DeviceFailureEmailService::class);
        $emailServiceMock->shouldReceive('sendFailureEmail')->once();
        app()->instance(DeviceFailureEmailService::class, $emailServiceMock);

        $this->stepExecutor->executeBookPolicyStep($quote, $process);

        // Assertion is in the mock expectation
    });
});

// Helper functions for creating mock objects

function createMockQuoteForStepExecutor(array $overrides = []): object
{
    $defaults = [
        'id' => 1,
        'code' => 'DEV-12345',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'mobile_no' => '+971501234567',
        'insurer_quote_number' => 'NGI-Q-12345',
        'policy_start_date' => '2024-01-15',
        'policy_expiry_date' => '2025-01-15',
        'policy_number' => null,
    ];

    return (object) array_merge($defaults, $overrides);
}

function createMockProcessForStepExecutor(array $overrides = []): object
{
    $defaults = [
        'id' => 1,
        'status' => PolicyIssuanceEnum::PROCESSING_STATUS,
        'completed_step' => null,
    ];

    return (object) array_merge($defaults, $overrides);
}
