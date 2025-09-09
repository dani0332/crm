<?php

namespace App\Observers;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Models\PolicyIssuance;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\Car\LivaInsuranceService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;

class PolicyIssuanceObserver
{
    /**
     * Handle the PolicyIssuance "updated" event.
     */
    public function updated(PolicyIssuance $policyIssuance): void
    {
        $policyIssuance->getDirty();
        LoggerService::startQuoteLogging($policyIssuance->model);
        LoggerService::info('PolicyIssuanceObserver fn:'.__FUNCTION__.' - Start Policy Issuance ID : '.$policyIssuance->id, extra: [
            'status' => $policyIssuance->status,
            'completed_step' => $policyIssuance->completed_step,
        ]);

        if (
            $policyIssuance->isDirty('status') &&
            $policyIssuance->insuranceProvider->code === InsuranceProvidersEnum::RSA &&
            $policyIssuance->status === PolicyIssuanceEnum::FAILED_STATUS &&
            $policyIssuance->completed_step === LivaInsuranceService::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM &&
            str_contains($policyIssuance->message, 'PolicyIssuanceJob has been attempted too many times')
        ) {
            LoggerService::info('PolicyIssuanceObserver fn:'.__FUNCTION__.' - Updating Policy Issuance ID : '.$policyIssuance->id.' - Status : '.PolicyIssuanceEnum::PENDING_STATUS);
            try {
                $policyIssuance->update([
                    'status' => PolicyIssuanceEnum::PENDING_STATUS,
                    'message' => 'null'
                ]);
            } catch (\Exception $ex) {
                LoggerService::info('PolicyIssuanceObserver fn:'.__FUNCTION__.' - Error Updating Policy Issuance ID : '.$policyIssuance->id, extra: [
                    'errorMessage' => $ex->getMessage(),
                ]);
            }
        } elseif (
            $policyIssuance->isDirty('status') &&
            $policyIssuance->status === PolicyIssuanceEnum::BOOKING_PENDING_STATUS &&
            $policyIssuance->insuranceProvider->code === InsuranceProvidersEnum::AXA
        ) {
            app(PolicyIssuanceService::class)->executePolicyIssuanceAutomationSteps();
        }

        LoggerService::info('PolicyIssuanceObserver fn:'.__FUNCTION__.' - End Policy Issuance ID : '.$policyIssuance->id);
    }
}
