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
use GuzzleHttp\Exception\ConnectException as GuzzleConnectException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException as IlluminateConnectionException;
use Illuminate\Http\Client\RequestException as IlluminateRequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Runs one DIC Travel EnsuredIT step asynchronously so {@see PolicyIssuanceJob} can dispatch-only and exit.
 *
 * Implements ShouldBeUniqueUntilProcessing (not ShouldBeUnique): the unique lock is released before `handle()` runs,
 * so a delayed retry dispatch for the same step from inside `handle()` is not dropped as a duplicate.
 */
class DicPolicyIssuanceStepJob implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const QUEUE_NAME = 'policy-issuance-automation';

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
        $this->onQueue(self::QUEUE_NAME);
        $retryDelaySeconds = (int) getAppStorageValueByKey(ApplicationStorageEnums::DIC_TRAVEL_ASYNC_RETRY_DELAY_SECONDS, 90, true);
        $this->uniqueFor = max(120, $retryDelaySeconds + 30);
    }

    public function uniqueId(): string
    {
        return 'dic-travel-policy-step-'.$this->policyIssuanceId.'-'.$this->step;
    }

    public function handle(DicInsuranceService $dicInsuranceService, PolicyIssuanceService $policyIssuanceService): void
    {
        if (! $dicInsuranceService->isPolicyIssuanceAutomationEnabled()) {
            $process = PolicyIssuance::query()->find($this->policyIssuanceId);
            if ($process) {
                $this->markProcessFailed($process, 'DIC Travel automation is disabled');
            } else {
                LoggerService::info('DIC async step skipped: policy issuance not found', [
                    'policy_issuance_id' => $this->policyIssuanceId,
                    'step' => $this->step,
                ]);
            }

            return;
        }

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
            $this->continueDicAsyncPipeline($process, $quote, $dicInsuranceService, $policyIssuanceService);
        }
    }

    private function continueDicAsyncPipeline(
        PolicyIssuance $process,
        TravelQuote $quote,
        DicInsuranceService $dicInsuranceService,
        PolicyIssuanceService $policyIssuanceService,
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

        $validation = $dicInsuranceService->validateBeforeDicAsyncRun($quote);
        if (! $validation['status']) {
            $message = $validation['error'] ?? $validation['message'] ?? 'DIC validation failed';
            $this->markProcessFailed($process, $message);

            return;
        }

        $this->runDicStepAfterValidationPassed($process, $quote, $dicInsuranceService, $policyIssuanceService);
    }

    private function runDicStepAfterValidationPassed(
        PolicyIssuance $process,
        TravelQuote $quote,
        DicInsuranceService $dicInsuranceService,
        PolicyIssuanceService $policyIssuanceService,
    ): void {
        $stepAttemptLogBaseline = $this->countAttemptLogsForStep($process->id, $this->step);

        try {
            $stepResponse = $dicInsuranceService->runSingleDicAsyncStep($quote, $process, $this->step, false);
        } catch (Throwable $e) {
            LoggerService::error('DIC Travel: async step threw exception', [
                'policy_issuance_id' => $process->id,
                'step' => $this->step,
                'quote_code' => $quote->code,
            ], exception: $e);

            if (! $this->isRetryableDicAsyncTransportFailure($e)) {
                $stepResponse = [
                    'status' => false,
                    'error' => $e->getMessage(),
                    'message' => $e->getMessage(),
                ];
                $this->finalizeDicAsyncStepAsPermanentlyFailed($process, $quote, $dicInsuranceService, $policyIssuanceService, $stepResponse);

                return;
            }

            $logsAfterStepAttempt = $this->countAttemptLogsForStep($process->id, $this->step);
            if ($logsAfterStepAttempt === $stepAttemptLogBaseline) {
                $policyIssuanceService->storePolicyIssuanceLog(
                    $quote,
                    [],
                    [
                        'error' => $e->getMessage(),
                        'exception_class' => $e::class,
                        'handler' => 'dic_async_exception',
                    ],
                    'dic-async-step/unhandled-exception',
                    $this->step,
                    PolicyIssuanceEnum::FAILED_STATUS,
                    $process,
                );
            }

            $stepResponse = [
                'status' => false,
                'error' => $e->getMessage(),
                'message' => $e->getMessage(),
            ];
        }

        if ($stepResponse['status'] ?? false) {
            $dicInsuranceService->updateProcessCompletedStepFromResponse($process->fresh(), $stepResponse);
            $process->refresh();

            $next = $dicInsuranceService->resolveAsyncStepToRun($process);
            if ($next === null) {
                $policyIssuanceService->applyTravelDicAutomationResult($quote->fresh(), true);
                $process->update(['status' => PolicyIssuanceEnum::COMPLETED_STATUS]);
                LoggerService::info('DIC Travel: async pipeline completed', [
                    'policy_issuance_id' => $process->id,
                    'quote_code' => $quote->code,
                ]);
            } else {
                self::dispatch($process->id, $next)->onQueue(self::QUEUE_NAME);
                LoggerService::info('DIC Travel: async next step dispatched', [
                    'policy_issuance_id' => $process->id,
                    'next_step' => $next,
                ]);
            }

            return;
        }

        // Max attempts per step includes each persisted issuance-log row for this step ({@see DicApiService}). Retryable
        // exceptions after the service already logged must not add a second row — handled via baseline in catch above.
        $attemptCountForStep = $this->countAttemptLogsForStep($process->id, $this->step);

        $maxAttemptsPerStep = (int) getAppStorageValueByKey(ApplicationStorageEnums::DIC_TRAVEL_ASYNC_MAX_FAILED_ATTEMPTS_PER_STEP, 3);
        $retryDelaySeconds = (int) getAppStorageValueByKey(ApplicationStorageEnums::DIC_TRAVEL_ASYNC_RETRY_DELAY_SECONDS, 90);

        if ($attemptCountForStep < $maxAttemptsPerStep) {
            self::dispatch($process->id, $this->step)
                ->delay(now()->addSeconds($retryDelaySeconds))
                ->onQueue(self::QUEUE_NAME);

            LoggerService::info('DIC Travel: async step failed, retry scheduled', [
                'policy_issuance_id' => $process->id,
                'step' => $this->step,
                'attempt_count_for_step' => $attemptCountForStep,
                'max_attempts_per_step' => $maxAttemptsPerStep,
                'delay_seconds' => $retryDelaySeconds,
            ]);

            return;
        }

        $this->finalizeDicAsyncStepAsPermanentlyFailed($process, $quote, $dicInsuranceService, $policyIssuanceService, $stepResponse);

        LoggerService::info('DIC Travel: async step failed after max attempts', [
            'policy_issuance_id' => $process->id,
            'step' => $this->step,
            'attempt_count_for_step' => $attemptCountForStep,
        ]);
    }

    /**
     * Laravel HTTP layer and raw Guzzle connect failures (timeouts, resets, refusal after client retries exhausted).
     *
     * Anything else—including {@see \Error} (type/parse failures) and non-transport {@see \Exception}s—does not retry.
     */
    private function isRetryableDicAsyncTransportFailure(Throwable $e): bool
    {
        if ($e instanceof \Error) {
            return false;
        }

        return $e instanceof IlluminateConnectionException
            || $e instanceof IlluminateRequestException
            || $e instanceof GuzzleConnectException;
    }

    /**
     * @param  array<string, mixed>  $stepResponse
     */
    private function finalizeDicAsyncStepAsPermanentlyFailed(
        PolicyIssuance $process,
        TravelQuote $quote,
        DicInsuranceService $dicInsuranceService,
        PolicyIssuanceService $policyIssuanceService,
        array $stepResponse,
    ): void {
        $ctx = $dicInsuranceService->resolveTravelDicAsyncFailureContext($this->step, $stepResponse);

        $policyIssuanceService->applyTravelDicAutomationResult(
            $quote->fresh(),
            false,
            $ctx['insurer_api_status_id'],
            $ctx['process_involved'],
        );

        $process->update([
            'status' => PolicyIssuanceEnum::FAILED_STATUS,
            'message' => json_encode([
                'error' => $stepResponse['error'] ?? $stepResponse['message'] ?? 'DIC async step permanently failed',
            ]),
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
