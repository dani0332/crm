<?php

declare(strict_types=1);

use App\Enums\NgiEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiBookPolicyService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiQuoteUpdaterService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiResponseHandler;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiValidationService;

test('processing status with completed documents step only enables booking details editing', function (): void {
    /** @var NgiValidationService $validationService */
    $validationService = Mockery::mock(NgiValidationService::class);
    /** @var NgiResponseHandler $responseHandler */
    $responseHandler = Mockery::mock(NgiResponseHandler::class);
    /** @var NgiQuoteUpdaterService $quoteUpdater */
    $quoteUpdater = Mockery::mock(NgiQuoteUpdaterService::class);

    $service = new NgiBookPolicyService(
        $validationService,
        $responseHandler,
        $quoteUpdater,
    );

    $quote = (object) [
        'id' => 123,
        'code' => 'SP-123',
        'insurer_api_status' => 'processing',
        'policyIssuance' => (object) [
            'status' => PolicyIssuanceEnum::PROCESSING_STATUS,
            'completed_step' => NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
        ],
    ];

    $lockingStatus = $service->getStepsLockingStatus($quote);

    expect($lockingStatus['isEditPolicyDetailsDisabled'])->toBeTrue()
        ->and($lockingStatus['isEditBookingDetailsDisabled'])->toBeFalse()
        ->and($lockingStatus['message'])->toBe('Only Update Booking Details is editable');
});
