<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\InsuranceProviderEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Models\PolicyIssuance;
use App\Models\PolicyIssuanceLog;
use App\Models\TravelQuote;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicInsuranceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Runs one DIC Travel EnsuredIT step asynchronously so {@see PolicyIssuanceJob} can dispatch-only and exit.
 */
class DicPolicyIssuanceStepJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;
    public int $tries = 1;

    /**
     * Longer than {@see ApplicationStorageEnums::DIC_TRAVEL_ASYNC_RETRY_DELAY_SECONDS} so delayed retries do not stack duplicate unique locks.
     */
    public int $uniqueFor = 120;

    public function __construct(
        public int $policyIssuanceId,
        public string $step,
    ) {
        $this->onQueue('policy-issuance-automation');
        $retryDelaySeconds = (int) getAppStorageValueByKey(ApplicationStorageEnums::DIC_TRAVEL_ASYNC_RETRY_DELAY_SECONDS, 90, true);
        $this->uniqueFor = max(120, $retryDelaySeconds + 30);
    }

    public function uniqueId(): string
    {
        return 'dic-travel-policy-step-'.$this->policyIssuanceId.'-'.$this->step;
    }

    public function handle(DicInsuranceService $dicInsuranceService): void
    {
        $process = PolicyIssuance::query()
            ->with(['insuranceProvider', 'model'])
            ->find($this->policyIssuanceId);

        if (! $process) {
            LoggerService::info('DIC async step skipped: policy issuance not found', [
                'policy_issuance_id' => $this->policyIssuanceId,
                'step' => $this->step,
            ]);
        } elseif ($process->quote_type !== QuoteTypes::TRAVEL->value
            || $process->insuranceProvider?->code !== InsuranceProviderEnum::DIC->value) {
            LoggerService::info('DIC async step skipped: not DIC travel', [
                'policy_issuance_id' => $process->id,
                'step' => $this->step,
            ]);
        } elseif (! in_array($process->status, [
            PolicyIssuanceEnum::PROCESSING_STATUS,
            PolicyIssuanceEnum::BOOKING_PROCESSING_STATUS,
        ], true)) {
            LoggerService::info('DIC async step skipped: issuance not in processing state', [
                'policy_issuance_id' => $process->id,
                'step' => $this->step,
                'status' => $process->status,
            ]);
        } elseif (! ($quote = $process->model) instanceof TravelQuote) {
            $this->markProcessFailed($process, 'Invalid quote model for DIC Travel');
        } else {
            $this->continueDicAsyncPipeline($process, $quote, $dicInsuranceService);
        }
    }

    private function continueDicAsyncPipeline(
        PolicyIssuance $process,
        TravelQuote $quote,
        DicInsuranceService $dicInsuranceService,
    ): void {
        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::DIC_TRAVEL_POLICY_AUTOMATION);

        $expectedStep = $dicInsuranceService->resolveAsyncStepToRun($process->fresh());
        if ($expectedStep !== $this->step) {
            LoggerService::info('DIC async step skipped: step mismatch (state already advanced or stale job)', [
                'policy_issuance_id' => $process->id,
                'expected_step' => $expectedStep,
                'job_step' => $this->step,
                'completed_step' => $process->completed_step,
            ]);

            return;
        }

        if (! $dicInsuranceService->isPolicyIssuanceAutomationEnabled()) {
            $this->markProcessFailed($process, 'DIC Travel automation is disabled');

            return;
        }

        $validation = $dicInsuranceService->validateBeforeDicAsyncRun($quote);
        if (! $validation['status']) {
            $message = $validation['error'] ?? $validation['message'] ?? 'DIC validation failed';
            $this->markProcessFailed($process, $message);

            return;
        }

        $this->runDicStepAfterValidationPassed($process, $quote, $dicInsuranceService);
    }

    private function runDicStepAfterValidationPassed(
        PolicyIssuance $process,
        TravelQuote $quote,
        DicInsuranceService $dicInsuranceService,
    ): void {
        $stepResponse = $dicInsuranceService->runSingleDicAsyncStep($quote, $process, $this->step, false);

        if ($stepResponse['status'] ?? false) {
            $dicInsuranceService->updateProcessCompletedStepFromResponse($process->fresh(), $stepResponse);
            $process->refresh();

            $next = $dicInsuranceService->resolveAsyncStepToRun($process);
            if ($next === null) {
                $process->update(['status' => PolicyIssuanceEnum::COMPLETED_STATUS]);
                LoggerService::info('DIC Travel: async pipeline completed', [
                    'policy_issuance_id' => $process->id,
                    'quote_code' => $quote->code,
                ]);
            } else {
                self::dispatch($process->id, $next)->onQueue('policy-issuance-automation');
                LoggerService::info('DIC Travel: async next step dispatched', [
                    'policy_issuance_id' => $process->id,
                    'next_step' => $next,
                ]);
            }

            return;
        }

        // Application storage value is the max total attempts per step (including this one). Count DB rows so
        // retries align with actual API attempts and with {@see DicApiService} logging (step outcome, not raw HTTP).
        $attemptCountForStep = $this->countAttemptLogsForStep($process->id, $this->step);

        $maxAttemptsPerStep = (int) getAppStorageValueByKey(ApplicationStorageEnums::DIC_TRAVEL_ASYNC_MAX_FAILED_ATTEMPTS_PER_STEP, 3);
        $retryDelaySeconds = (int) getAppStorageValueByKey(ApplicationStorageEnums::DIC_TRAVEL_ASYNC_RETRY_DELAY_SECONDS, 90);

        if ($attemptCountForStep < $maxAttemptsPerStep) {
            self::dispatch($process->id, $this->step)
                ->delay(now()->addSeconds($retryDelaySeconds))
                ->onQueue('policy-issuance-automation');

            LoggerService::info('DIC Travel: async step failed, retry scheduled', [
                'policy_issuance_id' => $process->id,
                'step' => $this->step,
                'attempt_count_for_step' => $attemptCountForStep,
                'max_attempts_per_step' => $maxAttemptsPerStep,
                'delay_seconds' => $retryDelaySeconds,
            ]);

            return;
        }

        $ctx = $dicInsuranceService->resolveTravelDicAsyncFailureContext($this->step, $stepResponse);
        app(PolicyIssuanceService::class)->applyTravelDicAutomationFailure(
            $quote->fresh(),
            $ctx['insurer_api_status_id'],
            $ctx['process_involved'],
        );

        $process->update([
            'status' => PolicyIssuanceEnum::FAILED_STATUS,
            'message' => json_encode([
                'error' => $stepResponse['error'] ?? $stepResponse['message'] ?? 'DIC step failed after max retries',
            ]),
        ]);

        LoggerService::info('DIC Travel: async step failed after max attempts', [
            'policy_issuance_id' => $process->id,
            'step' => $this->step,
            'attempt_count_for_step' => $attemptCountForStep,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $process = PolicyIssuance::find($this->policyIssuanceId);
        if (! $process) {
            return;
        }

        $process->update([
            'status' => PolicyIssuanceEnum::FAILED_STATUS,
            'message' => json_encode(['error' => 'DIC async step job failed: '.$exception->getMessage()]),
        ]);

        LoggerService::error('DIC async step job failed callback', [
            'policy_issuance_id' => $this->policyIssuanceId,
            'step' => $this->step,
        ], exception: $exception);
    }

    private function countAttemptLogsForStep(int $policyIssuanceId, string $step): int
    {
        return PolicyIssuanceLog::query()
            ->where('policy_issuance_id', $policyIssuanceId)
            ->where('step', $step)
            ->count();
    }

    private function markProcessFailed(PolicyIssuance $process, string $message): void
    {
        $process->update([
            'status' => PolicyIssuanceEnum::FAILED_STATUS,
            'message' => json_encode(['error' => $message]),
        ]);
    }
}
