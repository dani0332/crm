<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Health\Adnic;

use App\Enums\AdnicEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Enums\SendPolicyTypeEnum;
use App\Http\Requests\BookPolicyRequest;
use App\Services\CentralService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\SageApiService;
use App\Traits\GenericQueriesAllLobs;
use Exception;
use Illuminate\Support\Facades\Validator;

class AdnicBookPolicyService
{
    use GenericQueriesAllLobs;

    public function __construct(
        private AdnicValidationService $validationService,
        private AdnicResponseHandler $responseHandler,
    ) {}

    /**
     * Get steps locking status for UI
     *
     * @param  mixed  $quote
     * @param  bool  $throughAutomation
     */
    public function getStepsLockingStatus($quote, $throughAutomation = false): array
    {
        LoggerService::info('Getting steps locking status for Health quote', extra: [
            'quote_id' => $quote->id,
            'quote_type' => QuoteTypes::HEALTH->value,
            'quote_code' => $quote->code,
        ]);
        $policyIssuance = $quote->policyIssuance;

        $response = [
            'policyIssuance' => $policyIssuance,
            'isEditPolicyDetailsDisabled' => true,
            'isEditBookingDetailsDisabled' => true,
            'message' => 'All steps are locked',
            'insurer_api_status' => $quote->insurer_api_status,
        ];

        // Early exit (first return)
        if ($throughAutomation) {
            $response['isEditPolicyDetailsDisabled'] = false;
            $response['isEditBookingDetailsDisabled'] = false;
            $response['message'] = AdnicEnum::ALL_STEPS_ARE_EDITABLE;

            return $response;
        }

        $shouldHandlePolicyIssuanceLogic = (
            $policyIssuance?->status === PolicyIssuanceEnum::FAILED_STATUS ||
            ($policyIssuance?->completed_step && $policyIssuance?->status == '')
        );

        if ($shouldHandlePolicyIssuanceLogic) {
            if (! $policyIssuance?->completed_step || $policyIssuance?->completed_step === AdnicEnum::STEP_UPLOAD_DOCUMENTS) {
                $response['isEditPolicyDetailsDisabled'] = false;
                $response['isEditBookingDetailsDisabled'] = false;
                $response['message'] = AdnicEnum::ALL_STEPS_ARE_EDITABLE;
            } elseif ($policyIssuance?->completed_step === AdnicEnum::STEP_ISSUE_POLICY) {
                $response['isEditPolicyDetailsDisabled'] = false;
                $response['isEditBookingDetailsDisabled'] = false;
                $response['message'] = 'Upload Documents and Update Booking Details are editable';
            } elseif ($policyIssuance?->completed_step === AdnicEnum::STEP_UPLOAD_POLICY_DOCS) {
                $response['isEditPolicyDetailsDisabled'] = false;
                $response['isEditBookingDetailsDisabled'] = false;
                $response['message'] = 'Booking Details is editable';
            }

            // Single return for this group
            return $response;
        }

        // handle no policyIssuance
        if (! $policyIssuance) {
            $response['isEditPolicyDetailsDisabled'] = false;
            $response['isEditBookingDetailsDisabled'] = false;
            $response['message'] = AdnicEnum::ALL_STEPS_ARE_EDITABLE;
        } elseif (
            $policyIssuance?->status === PolicyIssuanceEnum::PROCESSING_STATUS &&
            $policyIssuance?->completed_step === AdnicEnum::STEP_UPLOAD_POLICY_DOCS
        ) {
            $response['isEditBookingDetailsDisabled'] = false;
            $response['message'] = AdnicEnum::ALL_STEPS_ARE_EDITABLE;
        }

        // Final return, covers all remaining paths
        return $response;
    }

}
