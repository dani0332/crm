<?php

declare(strict_types=1);

use App\Enums\AdnicEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicBookPolicyService;

beforeEach(function () {
    $this->service = new AdnicBookPolicyService;
});

afterEach(function () {
    Mockery::close();
});

// CRITICAL TEST: Service initialization
test('book policy service initializes correctly', function () {
    expect($this->service)->toBeInstanceOf(AdnicBookPolicyService::class);
});

// CRITICAL TEST: Steps locking with automation enabled
test('get steps locking status returns all editable for automation', function () {
    $quote = new stdClass;
    $quote->id = 1;
    $quote->code = 'HQ123';
    $quote->insurer_api_status = null;
    $quote->policyIssuance = null;

    $result = $this->service->getStepsLockingStatus($quote, true);

    expect($result['isEditPolicyDetailsDisabled'])->toBeFalse()
        ->and($result['message'])->toBe(AdnicEnum::ALL_STEPS_ARE_EDITABLE);
});

// CRITICAL TEST: No policy issuance record
test('get steps locking status returns all editable when no policy issuance', function () {
    $quote = new stdClass;
    $quote->id = 1;
    $quote->code = 'HQ123';
    $quote->insurer_api_status = null;
    $quote->policyIssuance = null;

    $result = $this->service->getStepsLockingStatus($quote, false);

    expect($result['isEditPolicyDetailsDisabled'])->toBeFalse()
        ->and($result['message'])->toBe(AdnicEnum::ALL_STEPS_ARE_EDITABLE);
});

// CRITICAL TEST: Failed policy issuance with no completed step
test('get steps locking status returns all editable for failed status with no step', function () {
    $policyIssuance = new stdClass;
    $policyIssuance->status = PolicyIssuanceEnum::FAILED_STATUS;
    $policyIssuance->completed_step = null;

    $quote = new stdClass;
    $quote->id = 1;
    $quote->code = 'HQ123';
    $quote->insurer_api_status = null;
    $quote->policyIssuance = $policyIssuance;

    $result = $this->service->getStepsLockingStatus($quote, false);

    expect($result['isEditPolicyDetailsDisabled'])->toBeFalse()
        ->and($result['message'])->toBe(AdnicEnum::ALL_STEPS_ARE_EDITABLE);
});

// CRITICAL TEST: Failed policy issuance after upload documents step completed
test('get steps locking status after upload documents step reflects next step is issue policy', function () {
    $policyIssuance = new stdClass;
    $policyIssuance->status = PolicyIssuanceEnum::FAILED_STATUS;
    $policyIssuance->completed_step = AdnicEnum::STEP_UPLOAD_DOCUMENTS;

    $quote = new stdClass;
    $quote->id = 1;
    $quote->code = 'HQ123';
    $quote->insurer_api_status = null;
    $quote->policyIssuance = $policyIssuance;

    $result = $this->service->getStepsLockingStatus($quote, false);

    expect($result['isEditPolicyDetailsDisabled'])->toBeFalse()
        ->and($result['message'])->toBe('Issue Policy and Update Booking Details are editable');
});

// CRITICAL TEST: Failed policy issuance at issue policy step
test('get steps locking status handles failed at issue policy step', function () {
    $policyIssuance = new stdClass;
    $policyIssuance->status = PolicyIssuanceEnum::FAILED_STATUS;
    $policyIssuance->completed_step = AdnicEnum::STEP_ISSUE_POLICY;

    $quote = new stdClass;
    $quote->id = 1;
    $quote->code = 'HQ123';
    $quote->insurer_api_status = null;
    $quote->policyIssuance = $policyIssuance;

    $result = $this->service->getStepsLockingStatus($quote, false);

    expect($result['isEditPolicyDetailsDisabled'])->toBeFalse()
        ->and($result['message'])->toBe('Policy document retrieval and Booking Details are editable');
});

// CRITICAL TEST: Failed policy issuance at upload policy docs step
test('get steps locking status handles failed at upload policy docs step', function () {
    $policyIssuance = new stdClass;
    $policyIssuance->status = PolicyIssuanceEnum::FAILED_STATUS;
    $policyIssuance->completed_step = AdnicEnum::STEP_UPLOAD_POLICY_DOCS;

    $quote = new stdClass;
    $quote->id = 1;
    $quote->code = 'HQ123';
    $quote->insurer_api_status = null;
    $quote->policyIssuance = $policyIssuance;

    $result = $this->service->getStepsLockingStatus($quote, false);

    expect($result['isEditPolicyDetailsDisabled'])->toBeFalse()
        ->and($result['message'])->toBe('Booking Details is editable');
});

// CRITICAL TEST: Response structure consistency
test('get steps locking status always returns consistent structure', function () {
    $quote = new stdClass;
    $quote->id = 1;
    $quote->code = 'HQ123';
    $quote->insurer_api_status = null;
    $quote->policyIssuance = null;

    $result = $this->service->getStepsLockingStatus($quote, false);

    expect($result)
        ->toHaveKeys(['policyIssuance', 'isEditPolicyDetailsDisabled', 'message', 'insurer_api_status']);
});

// CRITICAL TEST: Completed step without failed status
test('get steps locking status handles completed step with empty status', function () {
    $policyIssuance = new stdClass;
    $policyIssuance->status = '';
    $policyIssuance->completed_step = AdnicEnum::STEP_ISSUE_POLICY;

    $quote = new stdClass;
    $quote->id = 1;
    $quote->code = 'HQ123';
    $quote->insurer_api_status = null;
    $quote->policyIssuance = $policyIssuance;

    $result = $this->service->getStepsLockingStatus($quote, false);

    expect($result['isEditPolicyDetailsDisabled'])->toBeFalse();
});
