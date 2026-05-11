<?php

declare(strict_types=1);

use App\Enums\NgiEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Facades\Ngi;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiBookPolicyService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiQuoteUpdaterService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiResponseHandler;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiValidationService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Database\Factories\DeviceQuoteFactory;

// Global variables for shared instances (Pest compatible)
$sharedResponseHandler = null;

beforeAll(function () {
    global $sharedResponseHandler;

    // Create shared stateless service instances once per test class
    $sharedResponseHandler = new NgiResponseHandler;
});

beforeEach(function () {
    global $sharedResponseHandler;

    // Create real dependencies where appropriate, mock others
    $this->validationService = Mockery::mock(NgiValidationService::class);
    $this->responseHandler = $sharedResponseHandler; // Reuse shared instance
    $this->quoteUpdater = Mockery::mock(NgiQuoteUpdaterService::class);
    $this->bookPolicyService = new NgiBookPolicyService(
        $this->validationService,
        $this->responseHandler,
        $this->quoteUpdater
    );
});

afterEach(function () {
    // Aggressive Mockery cleanup
    Mockery::close();
    Mockery::getContainer()->mockery_close();

    // Explicitly unset test properties to free memory (keep shared instances)
    unset($this->validationService, $this->bookPolicyService);
    // Note: $this->responseHandler is a shared instance, don't unset it

    // Clear service container bindings
    app()->forgetInstance(PolicyIssuanceService::class);

    // Reset Ngi facade to clear any mock instances
    Ngi::clearResolvedInstances();

    // Force garbage collection
    gc_collect_cycles();
});

describe('getStepsLockingStatus', function () {
    test('returns all steps editable when throughAutomation is true', function () {
        $quote = DeviceQuoteFactory::makeMock(['policyIssuance' => null, 'insurer_api_status' => null]);

        $result = $this->bookPolicyService->getStepsLockingStatus($quote, true);

        expect($result['isEditPolicyDetailsDisabled'])->toBeFalse()
            ->and($result['isEditBookingDetailsDisabled'])->toBeFalse()
            ->and($result['message'])->toBe(NgiEnum::ALL_STEPS_ARE_EDITABLE);
    });

    test('returns all steps editable when no policy issuance exists', function () {
        $quote = DeviceQuoteFactory::makeMock(['policyIssuance' => null, 'insurer_api_status' => null]);

        $result = $this->bookPolicyService->getStepsLockingStatus($quote, false);

        expect($result['isEditPolicyDetailsDisabled'])->toBeFalse()
            ->and($result['isEditBookingDetailsDisabled'])->toBeFalse()
            ->and($result['message'])->toBe(NgiEnum::ALL_STEPS_ARE_EDITABLE);
    });

    test('returns all steps editable when policy issuance status is failed', function () {
        $policyIssuance = (object) [
            'status' => PolicyIssuanceEnum::FAILED_STATUS,
            'completed_step' => null,
        ];
        $quote = DeviceQuoteFactory::makeMock(['policyIssuance' => $policyIssuance, 'insurer_api_status' => null]);

        $result = $this->bookPolicyService->getStepsLockingStatus($quote, false);

        expect($result['isEditPolicyDetailsDisabled'])->toBeFalse()
            ->and($result['isEditBookingDetailsDisabled'])->toBeFalse()
            ->and($result['message'])->toBe(NgiEnum::ALL_STEPS_ARE_EDITABLE);
    });

    test('returns all steps editable when completed step is GetAndUploadPolicyDocs and status is failed', function () {
        $policyIssuance = (object) [
            'status' => PolicyIssuanceEnum::FAILED_STATUS,
            'completed_step' => NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
        ];
        $quote = DeviceQuoteFactory::makeMock(['policyIssuance' => $policyIssuance, 'insurer_api_status' => null]);

        $result = $this->bookPolicyService->getStepsLockingStatus($quote, false);

        expect($result['isEditPolicyDetailsDisabled'])->toBeFalse()
            ->and($result['isEditBookingDetailsDisabled'])->toBeFalse();
    });

    test('returns editable steps when completed step is CreatePolicyFromQuote', function () {
        $policyIssuance = (object) [
            'status' => PolicyIssuanceEnum::FAILED_STATUS,
            'completed_step' => NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
        ];
        $quote = DeviceQuoteFactory::makeMock(['policyIssuance' => $policyIssuance, 'insurer_api_status' => null]);

        $result = $this->bookPolicyService->getStepsLockingStatus($quote, false);

        expect($result['isEditPolicyDetailsDisabled'])->toBeFalse()
            ->and($result['isEditBookingDetailsDisabled'])->toBeFalse();
    });

    test('returns booking editable when processing and documents completed', function () {
        $policyIssuance = (object) [
            'status' => PolicyIssuanceEnum::PROCESSING_STATUS,
            'completed_step' => NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
        ];
        $quote = DeviceQuoteFactory::makeMock(['policyIssuance' => $policyIssuance, 'insurer_api_status' => null]);

        $result = $this->bookPolicyService->getStepsLockingStatus($quote, false);

        expect($result['isEditBookingDetailsDisabled'])->toBeFalse();
    });

    test('returns locked steps when policy issuance is processing and at first step', function () {
        $policyIssuance = (object) [
            'status' => PolicyIssuanceEnum::PROCESSING_STATUS,
            'completed_step' => null,
        ];
        $quote = DeviceQuoteFactory::makeMock(['policyIssuance' => $policyIssuance, 'insurer_api_status' => null]);

        $result = $this->bookPolicyService->getStepsLockingStatus($quote, false);

        // When processing and no completed step, all should be locked
        expect($result['isEditPolicyDetailsDisabled'])->toBeTrue()
            ->and($result['isEditBookingDetailsDisabled'])->toBeTrue();
    });

    test('returns policyIssuance in result', function () {
        $policyIssuance = (object) [
            'status' => PolicyIssuanceEnum::COMPLETED_STATUS,
            'completed_step' => NgiEnum::STEP_BOOK_POLICY,
        ];
        $quote = DeviceQuoteFactory::makeMock(['policyIssuance' => $policyIssuance, 'insurer_api_status' => null]);

        $result = $this->bookPolicyService->getStepsLockingStatus($quote, false);

        expect($result)->toHaveKey('policyIssuance')
            ->and($result['policyIssuance'])->toBe($policyIssuance);
    });

    test('returns insurer_api_status in result', function () {
        $quote = DeviceQuoteFactory::makeMock([
            'policyIssuance' => null,
            'insurer_api_status' => 'FAILED',
        ]);

        $result = $this->bookPolicyService->getStepsLockingStatus($quote, false);

        expect($result)->toHaveKey('insurer_api_status')
            ->and($result['insurer_api_status'])->toBe('FAILED');
    });
});

describe('NgiResponseHandler integration', function () {
    test('response handler builds correct step response structure', function () {
        $response = $this->responseHandler->buildStepResponse(NgiEnum::STEP_BOOK_POLICY);

        expect($response)->toHaveKeys(['status', 'completed_step', 'message', 'error', 'data'])
            ->and($response['completed_step'])->toBe(NgiEnum::STEP_BOOK_POLICY);
    });

    test('response handler builds success response correctly', function () {
        $response = $this->responseHandler->buildStepResponse(
            NgiEnum::STEP_BOOK_POLICY,
            true,
            'Booking process started!',
            null,
            ['booking_id' => 123]
        );

        expect($response['status'])->toBeTrue()
            ->and($response['message'])->toBe('Booking process started!')
            ->and($response['data'])->toBe(['booking_id' => 123]);
    });
});
