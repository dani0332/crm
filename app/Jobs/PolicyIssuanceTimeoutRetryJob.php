<?php

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Models\PolicyIssuance;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicInsuranceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class PolicyIssuanceTimeoutRetryJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /**
     * Keep the unique lock longer than the scheduled delay so duplicate observer runs do not stack retries.
     */
    public int $uniqueFor = 600;

    public function __construct(public int $policyIssuanceId)
    {
        $this->onQueue('policy-issuance-automation');
    }

    public function uniqueId(): string
    {
        return 'policy-issuance-timeout-retry-'.$this->policyIssuanceId;
    }

    public function handle(AdnicInsuranceService $adnicInsuranceService): void
    {
        $policyIssuance = PolicyIssuance::query()->find($this->policyIssuanceId);

        if (! $policyIssuance) {
            LoggerService::info('ADNIC timeout retry job skipped: policy issuance not found', [
                'policy_issuance_id' => $this->policyIssuanceId,
            ]);

            return;
        }

        $this->processTimeoutRetryForExistingPolicyIssuance($policyIssuance, $adnicInsuranceService);
    }

    /**
     * Runs validation, optional quote logging, and the timeout→pending reset when all gates pass.
     */
    private function processTimeoutRetryForExistingPolicyIssuance(
        PolicyIssuance $policyIssuance,
        AdnicInsuranceService $adnicInsuranceService,
    ): void {
        $quote = $policyIssuance->model;
        if ($quote) {
            LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::ADNIC_HEALTH_POLICY_AUTOMATION);
        }

        if (! $adnicInsuranceService->isPolicyIssuanceAutomationRetryEnabledForTimeout()) {
            LoggerService::info('ADNIC timeout retry job skipped: retry automation disabled', [
                'policy_issuance_id' => $policyIssuance->id,
            ]);
        } elseif ($policyIssuance->status !== PolicyIssuanceEnum::TIMEOUT_STATUS) {
            LoggerService::info('ADNIC timeout retry job skipped: policy issuance is not in timeout status', [
                'policy_issuance_id' => $policyIssuance->id,
                'status' => $policyIssuance->status,
            ]);
        } else {
            $maxRetries = $adnicInsuranceService->getAllowRetryForTimeout();
            $currentRetryCount = (int) ($policyIssuance->retry_count ?? 0);

            if ($currentRetryCount >= $maxRetries) {
                LoggerService::info('ADNIC timeout retry job skipped: maximum retries reached', [
                    'policy_issuance_id' => $policyIssuance->id,
                    'retry_count' => $currentRetryCount,
                    'max_retries' => $maxRetries,
                ]);
            } else {
                try {
                    $policyIssuance->update([
                        'status' => PolicyIssuanceEnum::PENDING_STATUS,
                        'retry_count' => $currentRetryCount + 1,
                    ]);

                    LoggerService::info('ADNIC timeout retry applied: status reset to pending', [
                        'policy_issuance_id' => $policyIssuance->id,
                        'retry_count' => $currentRetryCount + 1,
                    ]);
                } catch (Throwable $e) {
                    LoggerService::error('ADNIC timeout retry job failed to update policy issuance', [
                        'policy_issuance_id' => $policyIssuance->id,
                    ], $e);

                    throw $e;
                }
            }
        }
    }
}
