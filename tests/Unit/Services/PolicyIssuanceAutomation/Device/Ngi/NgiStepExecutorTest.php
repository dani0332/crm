<?php

declare(strict_types=1);

use App\Enums\NgiEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Facades\Ngi;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiApiService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiBookPolicyService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiGetPolicyDocumentsJob;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiStepExecutor;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\PolicyIssuanceFailureEmailService;
use Illuminate\Support\Facades\Queue;

/**
 * Create a lightweight device quote stub optimized for quick unit tests.
 *
 * @param  array<string, mixed>  $overrides
 */
function makeDeviceQuoteStub(array $overrides = []): object
{
    static $prototype = null;

    if (! $prototype) {
        $prototype = (object) [
            'id' => 1,
            'insurer_quote_number' => 'NGI-Q-123',
            'policy_number' => null,
            'quote_type_id' => QuoteTypes::DEVICE->value,
            'email' => 'john.doe@example.com',
        ];
    }

    $quote = clone $prototype;

    foreach ($overrides as $key => $value) {
        $quote->{$key} = $value;
    }

    return $quote;
}

/**
 * Create a lightweight process stub optimized for quick unit tests.
 *
 * @param  array<string, mixed>  $overrides
 */
function makeProcessStub(array $overrides = []): object
{
    static $prototype = null;

    if (! $prototype) {
        $prototype = (object) [
            'id' => 1,
            'status' => PolicyIssuanceEnum::PROCESSING_STATUS,
            'completed_step' => null,
        ];
    }

    $process = clone $prototype;

    foreach ($overrides as $key => $value) {
        $process->{$key} = $value;
    }

    return $process;
}

beforeEach(function () {
    // Create mocked dependencies for unit tests
    $this->apiService = Mockery::mock(NgiApiService::class);
    $this->bookPolicyService = Mockery::mock(NgiBookPolicyService::class);
    $this->failureEmailService = Mockery::mock(PolicyIssuanceFailureEmailService::class);
    $this->failureEmailService->shouldIgnoreMissing();
    $this->failureEmailService->shouldReceive('isPolicyIssuanceFailureEmail')->andReturnFalse()->byDefault();
    $this->failureEmailService->shouldReceive('isBookPolicyFailureEmail')->andReturnFalse()->byDefault();

    $this->stepExecutor = new NgiStepExecutor(
        $this->apiService,
        $this->bookPolicyService,
        $this->failureEmailService,
    );
});

afterEach(function () {
    // Aggressive Mockery cleanup
    Mockery::close();
    Mockery::getContainer()->mockery_close();

    // Explicitly unset test properties to free memory
    unset($this->apiService, $this->bookPolicyService, $this->stepExecutor, $this->failureEmailService);

    // Clear service container bindings
    app()->forgetInstance(PolicyIssuanceService::class);

    // Reset Ngi facade to clear any mock instances
    Ngi::clearResolvedInstances();

    // Force garbage collection
    gc_collect_cycles();
});

describe('executeCreatePolicyFromQuoteStep', function () {
    test('returns success response when API call succeeds', function () {
        $quote = makeDeviceQuoteStub();
        $process = makeProcessStub();

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
        $quote = makeDeviceQuoteStub();
        $process = makeProcessStub();

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

        $result = $this->stepExecutor->executeCreatePolicyFromQuoteStep($quote, $process);

        expect($result['status'])->toBeFalse()
            ->and($result['error'])->toBe('Invalid quote number');
    });

    test('updates insurer API status when API call fails', function () {
        $quote = makeDeviceQuoteStub();
        $process = makeProcessStub();

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
                QuoteTypes::DEVICE->value,
                PolicyIssuanceEnum::PIA_POLICY_ISSUANCE_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID,
                PolicyIssuanceEnum::PROCESS_INVOLVED_ISSUE_POLICY
            );
        app()->instance(PolicyIssuanceService::class, $policyIssuanceServiceMock);

        $this->stepExecutor->executeCreatePolicyFromQuoteStep($quote, $process);

        // Assertion is in the mock expectation
    });
});

describe('executeGetPolicyDocumentsAndUploadToIMCRMStep', function () {
    test('dispatches job with delay and returns documents_pending response', function () {
        Queue::fake();

        $quote = makeDeviceQuoteStub(['policy_number' => 'NGI-POL-123']);
        $process = makeProcessStub();

        $result = $this->stepExecutor->executeGetPolicyDocumentsAndUploadToIMCRMStep($quote, $process);

        expect($result['status'])->toBeTrue()
            ->and($result['documents_pending'])->toBeTrue()
            ->and($result['completed_step'])->toBe(NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE)
            ->and($result['message'])->toContain('dispatched');

        Queue::assertPushed(NgiGetPolicyDocumentsJob::class);
    });

    test('includes delay minutes in response message', function () {
        Queue::fake();

        $quote = makeDeviceQuoteStub(['policy_number' => 'NGI-POL-123']);
        $process = makeProcessStub();

        $result = $this->stepExecutor->executeGetPolicyDocumentsAndUploadToIMCRMStep($quote, $process);

        $delayMinutes = NgiEnum::DOCUMENT_FETCH_DELAY_MINUTES;
        expect($result['message'])->toContain((string) $delayMinutes.'-minute delay');
    });
});

describe('executeBookPolicyStep', function () {
    test('returns success response when book policy succeeds', function () {
        $quote = makeDeviceQuoteStub(['policy_number' => 'NGI-POL-123']);
        $process = makeProcessStub();

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
        $quote = makeDeviceQuoteStub(['policy_number' => 'NGI-POL-123']);
        $process = makeProcessStub();

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

        $result = $this->stepExecutor->executeBookPolicyStep($quote, $process);

        expect($result['status'])->toBeFalse()
            ->and($result['error'])->toBe('Sage API failed');
    });

    test('updates insurer API status when book policy fails', function () {
        $quote = makeDeviceQuoteStub(['policy_number' => 'NGI-POL-123']);
        $process = makeProcessStub();

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
                QuoteTypes::DEVICE->value,
                PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID,
                PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY
            );
        app()->instance(PolicyIssuanceService::class, $policyIssuanceServiceMock);

        $this->stepExecutor->executeBookPolicyStep($quote, $process);

        // Assertion is in the mock expectation
    });
});
