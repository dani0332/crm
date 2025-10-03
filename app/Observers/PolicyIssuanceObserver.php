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
    private $className = 'PolicyIssuanceObserver';
    /**
     * Handle the PolicyIssuance "updated" event.
     */
    public function updated(PolicyIssuance $policyIssuance): void
    {
        $policyIssuance->getDirty();
        LoggerService::startQuoteLogging($policyIssuance->model);
        LoggerService::info($this->className.' fn:'.__FUNCTION__.' - Start Policy Issuance ID : '.$policyIssuance->id, extra: [
            'status' => $policyIssuance->status,
            'completed_step' => $policyIssuance->completed_step,
        ]);

        if (
            $policyIssuance->isDirty('status') &&
            $policyIssuance->insuranceProvider->code === InsuranceProvidersEnum::RSA &&
            $policyIssuance->status === PolicyIssuanceEnum::FAILED_STATUS
        ) {
            LoggerService::info($this->className.' fn:'.__FUNCTION__.' - Updating Policy Issuance ID : '.$policyIssuance->id.' - Status : '.PolicyIssuanceEnum::PENDING_STATUS);
            try {
                if (str_contains($policyIssuance->message, 'PolicyIssuanceJob has been attempted too many times')) {
                    LoggerService::info('PolicyIssuanceJob was failed due to timeout', extra: [
                        'reason' => $policyIssuance->message,
                    ]);

                    $policyIssuance->update([
                        'status' => PolicyIssuanceEnum::PENDING_STATUS,
                    ]);
                } else {
                    $failedLogs = $policyIssuance->policyIssuanceLogs->where('status', PolicyIssuanceEnum::FAILED_STATUS);
                    if ($failedLogs) {
                        $failedNullLogFound = $failedLogs->filter(function ($log) {
                            return str_contains($log->response, '"status": false, "message": null, "completed_step": null');
                        });

                        if ($failedNullLogFound) {
                            LoggerService::info('PolicyIssuanceJob was failed due to timeout', extra: [
                                'error' => $failedNullLogFound->response,
                            ]);

                            $policyIssuance->update([
                                'status' => PolicyIssuanceEnum::PENDING_STATUS,
                            ]);
                        }
                    }
                }
            } catch (\Exception $ex) {
                LoggerService::info($this->className.' fn:'.__FUNCTION__.' - Error Updating Policy Issuance ID : '.$policyIssuance->id, extra: [
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

        LoggerService::info($this->className.' fn:'.__FUNCTION__.' - End Policy Issuance ID : '.$policyIssuance->id);
    }
}
