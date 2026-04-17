<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Health\Adnic;

use App\Enums\AdnicEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;

class AdnicBookPolicyService
{
    use GenericQueriesAllLobs;

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
            'message' => 'All steps are locked',
            'insurer_api_status' => $quote->insurer_api_status,
        ];

        // Early exit (first return)
        if ($throughAutomation) {
            $response['isEditPolicyDetailsDisabled'] = false;
            $response['message'] = AdnicEnum::ALL_STEPS_ARE_EDITABLE;

            return $response;
        }

        $shouldHandlePolicyIssuanceLogic = (
            $policyIssuance?->status === PolicyIssuanceEnum::FAILED_STATUS ||
            ($policyIssuance?->completed_step && $policyIssuance?->status == '')
        );

        if ($shouldHandlePolicyIssuanceLogic) {
            // Step order matches AdnicInsuranceService::getAPISteps(): UploadDocuments → IssuePolicy → UploadPolicyDocs
            if (! $policyIssuance?->completed_step) {
                $response['isEditPolicyDetailsDisabled'] = false;
                $response['message'] = AdnicEnum::ALL_STEPS_ARE_EDITABLE;
            } elseif ($policyIssuance?->completed_step === AdnicEnum::STEP_UPLOAD_DOCUMENTS) {
                $response['isEditPolicyDetailsDisabled'] = false;
                $response['message'] = 'Issue Policy and Update Booking Details are editable';
            } elseif ($policyIssuance?->completed_step === AdnicEnum::STEP_ISSUE_POLICY) {
                $response['isEditPolicyDetailsDisabled'] = false;
                $response['message'] = 'Policy document retrieval and Booking Details are editable';
            } elseif ($policyIssuance?->completed_step === AdnicEnum::STEP_UPLOAD_POLICY_DOCS) {
                $response['isEditPolicyDetailsDisabled'] = false;
                $response['message'] = 'Booking Details is editable';
            }

            return $response;
        }

        // handle no policyIssuance
        if (! $policyIssuance) {
            $response['isEditPolicyDetailsDisabled'] = false;
            $response['message'] = AdnicEnum::ALL_STEPS_ARE_EDITABLE;
        }

        // Final return, covers all remaining paths
        return $response;
    }

}
