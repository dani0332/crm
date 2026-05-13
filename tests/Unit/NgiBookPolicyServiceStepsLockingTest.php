<?php

declare(strict_types=1);

use App\Enums\NgiEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiBookPolicyService;

test('failed issuance after create policy step exposes same message as other fully editable states', function () {
    $policyIssuance = new stdClass;
    $policyIssuance->status = PolicyIssuanceEnum::FAILED_STATUS;
    $policyIssuance->completed_step = NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE;

    $quote = new stdClass;
    $quote->id = 1;
    $quote->code = 'DEV-TEST';
    $quote->policyIssuance = $policyIssuance;
    $quote->insurer_api_status = null;

    $result = app(NgiBookPolicyService::class)->getStepsLockingStatus($quote, false);

    expect($result['isEditPolicyDetailsDisabled'])->toBeFalse()
        ->and($result['isEditBookingDetailsDisabled'])->toBeFalse()
        ->and($result['message'])->toBe(NgiEnum::ALL_STEPS_ARE_EDITABLE);
});
