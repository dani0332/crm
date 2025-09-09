<?php

namespace App\Observers;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Models\PolicyIssuance;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\Car\LivaInsuranceService;

class PolicyIssuanceObserver
{
    /**
     * Handle the PolicyIssuance "updated" event.
     */
    public function updated(PolicyIssuance $policyIssuance): void
    {
        LoggerService::startQuoteLogging($policyIssuance->model);
        LoggerService::info('PolicyIssuanceObserver fn:'.__FUNCTION__.' - Start Policy Issuance ID : '.$policyIssuance->id, extra: [
            'status' => $policyIssuance->status,
            'completed_step' => $policyIssuance->completed_step,
        ]);

        if (
            in_array($policyIssuance->insuranceProvider->code, [InsuranceProvidersEnum::RSA]) &&
            in_array($policyIssuance->completed_step, [LivaInsuranceService::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM]) &&
            $policyIssuance->status === PolicyIssuanceEnum::FAILED_STATUS &&
            str_contains($policyIssuance->message, 'PolicyIssuanceJob has been attempted too many times.')
        ) {
            LoggerService::info('PolicyIssuanceObserver fn:'.__FUNCTION__.' - Updating Policy Issuance ID : '.$policyIssuance->id.' - Status : '.PolicyIssuanceEnum::PENDING_STATUS);
            try {
                $policyIssuance->update(['status' => PolicyIssuanceEnum::PENDING_STATUS]);
            } catch (\Exception $ex) {
                LoggerService::error('PolicyIssuanceObserver fn:'.__FUNCTION__.' - Error Updating Policy Issuance ID : '.$policyIssuance->id, exception: $ex);
            }
        }

        LoggerService::info('PolicyIssuanceObserver fn:'.__FUNCTION__.' - End Policy Issuance ID : '.$policyIssuance->id);
    }
}
