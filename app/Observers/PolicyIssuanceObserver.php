<?php

namespace App\Observers;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Models\PolicyIssuance;
use App\Services\Logger\LoggerService;
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
                $isTimeout = false;

                if (str_contains($policyIssuance->message, 'PolicyIssuanceJob has been attempted too many times')) {
                    LoggerService::info($this->className.' fn:'.__FUNCTION__.' - PolicyIssuanceJob was failed due to timeout', extra: [
                        'reason' => $policyIssuance->message,
                    ]);
                    $isTimeout = true;
                } else {
                    $failedLogs = $policyIssuance->policyIssuanceLogs->where('status', PolicyIssuanceEnum::FAILED_STATUS);
                    if ($failedLogs) {
                        $failedNullLogFound = $failedLogs->filter(function ($log) {
                            return $this->hasNullStatusResponse($log->response);
                        });

                        if ($failedNullLogFound->isNotEmpty()) {
                            LoggerService::info($this->className.' fn:'.__FUNCTION__.' - PolicyIssuanceJob was failed due to timeout', extra: [
                                'error' => $failedNullLogFound->first()?->response,
                            ]);
                            $isTimeout = true;
                        }
                    }
                }

                if ($isTimeout) {
                    $policyIssuance->update([
                        'status' => PolicyIssuanceEnum::TIMEOUT_STATUS,
                    ]);
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

    /**
     * Check if the response contains a null status indicating a timeout
     *
     * @param string $response The response JSON string to check
     * @return bool True if the response indicates a null status, false otherwise
     */
    private function hasNullStatusResponse(string $response): bool
    {
        $nullStatusPatterns = [
            '"status": false, "message": null, "completed_step": null',
            '"status": false, "message": "null", "completed_step": null',
        ];

        foreach ($nullStatusPatterns as $pattern) {
            if (str_contains($response, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
