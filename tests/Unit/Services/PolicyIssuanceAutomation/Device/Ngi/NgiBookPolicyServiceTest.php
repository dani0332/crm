<?php

declare(strict_types=1);

use App\Enums\NgiEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiBookPolicyService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiResponseHandler;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiValidationService;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    // Create minimal schema for tests that may hit database
    TestSchemaCreator::createMinimalSchema();

    // Create real dependencies where appropriate, mock others
    $this->validationService = Mockery::mock(NgiValidationService::class);
    $this->responseHandler = new NgiResponseHandler();

    $this->bookPolicyService = new NgiBookPolicyService(
        $this->validationService,
        $this->responseHandler
    );
});

afterEach(function () {
    Mockery::close();
});

describe('getStepsLockingStatus', function () {
    test('returns all steps editable when throughAutomation is true', function () {
        $quote = createMockQuoteForBookPolicyTest(['policyIssuance' => null, 'insurer_api_status' => null]);

        $result = $this->bookPolicyService->getStepsLockingStatus($quote, true);

        expect($result['isEditPolicyDetailsDisabled'])->toBeFalse()
            ->and($result['isEditBookingDetailsDisabled'])->toBeFalse()
            ->and($result['message'])->toBe(NgiEnum::ALL_STEPS_ARE_EDITABLE);
    });

    test('returns all steps editable when no policy issuance exists', function () {
        $quote = createMockQuoteForBookPolicyTest(['policyIssuance' => null, 'insurer_api_status' => null]);

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
        $quote = createMockQuoteForBookPolicyTest(['policyIssuance' => $policyIssuance, 'insurer_api_status' => null]);

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
        $quote = createMockQuoteForBookPolicyTest(['policyIssuance' => $policyIssuance, 'insurer_api_status' => null]);

        $result = $this->bookPolicyService->getStepsLockingStatus($quote, false);

        expect($result['isEditPolicyDetailsDisabled'])->toBeFalse()
            ->and($result['isEditBookingDetailsDisabled'])->toBeFalse();
    });

    test('returns editable steps when completed step is CreatePolicyFromQuote', function () {
        $policyIssuance = (object) [
            'status' => PolicyIssuanceEnum::FAILED_STATUS,
            'completed_step' => NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
        ];
        $quote = createMockQuoteForBookPolicyTest(['policyIssuance' => $policyIssuance, 'insurer_api_status' => null]);

        $result = $this->bookPolicyService->getStepsLockingStatus($quote, false);

        expect($result['isEditPolicyDetailsDisabled'])->toBeFalse()
            ->and($result['isEditBookingDetailsDisabled'])->toBeFalse();
    });

    test('returns booking editable when processing and documents completed', function () {
        $policyIssuance = (object) [
            'status' => PolicyIssuanceEnum::PROCESSING_STATUS,
            'completed_step' => NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
        ];
        $quote = createMockQuoteForBookPolicyTest(['policyIssuance' => $policyIssuance, 'insurer_api_status' => null]);

        $result = $this->bookPolicyService->getStepsLockingStatus($quote, false);

        expect($result['isEditBookingDetailsDisabled'])->toBeFalse();
    });

    test('returns locked steps when policy issuance is processing and at first step', function () {
        $policyIssuance = (object) [
            'status' => PolicyIssuanceEnum::PROCESSING_STATUS,
            'completed_step' => null,
        ];
        $quote = createMockQuoteForBookPolicyTest(['policyIssuance' => $policyIssuance, 'insurer_api_status' => null]);

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
        $quote = createMockQuoteForBookPolicyTest(['policyIssuance' => $policyIssuance, 'insurer_api_status' => null]);

        $result = $this->bookPolicyService->getStepsLockingStatus($quote, false);

        expect($result)->toHaveKey('policyIssuance')
            ->and($result['policyIssuance'])->toBe($policyIssuance);
    });

    test('returns insurer_api_status in result', function () {
        $quote = createMockQuoteForBookPolicyTest([
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

// Helper function for creating mock quote for book policy tests

function createMockQuoteForBookPolicyTest(array $overrides = []): object
{
    $defaults = [
        'id' => 1,
        'code' => 'DEV-12345',
        'policy_number' => 'NGI-POL-123',
        'policyIssuance' => null,
        'insurer_api_status' => null,
    ];

    $merged = array_merge($defaults, $overrides);

    return (object) $merged;
}
