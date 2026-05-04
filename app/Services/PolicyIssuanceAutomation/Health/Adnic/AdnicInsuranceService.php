<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Health\Adnic;

use App\Enums\AdnicEnum;
use App\Enums\ApplicationStorageEnums;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Interfaces\PolicyIssuanceInterface;
use App\Jobs\PolicyIssuanceTimeoutRetryJob;
use App\Models\PolicyIssuance;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Exception;

class AdnicInsuranceService implements PolicyIssuanceInterface
{
    private array $stepHandlers = [
        AdnicEnum::STEP_UPLOAD_DOCUMENTS => 'executeUploadDocumentsStep',
        AdnicEnum::STEP_ISSUE_POLICY => 'executeIssuePolicyStep',
        AdnicEnum::STEP_UPLOAD_POLICY_DOCS => 'executeUploadPolicyDocumentsStep',
    ];

    public function __construct(
        private AdnicStepExecutor $stepExecutor,
        private AdnicValidationService $validationService,
        private AdnicBookPolicyService $bookPolicyService,
        private AdnicResponseHandler $responseHandler,
    ) {}

    /**
     * Get API steps in order
     */
    private function getAPISteps(): array
    {
        return [
            AdnicEnum::STEP_UPLOAD_DOCUMENTS,
            AdnicEnum::STEP_ISSUE_POLICY,
            AdnicEnum::STEP_UPLOAD_POLICY_DOCS,
        ];
    }

    /**
     * Update process with completed step
     *
     * @param  mixed  $process
     */
    private function updateProcessWithStep($process, array $response): void
    {
        if (($response['status'] ?? false) && isset($response['completed_step'])) {
            $process->update(['completed_step' => $response['completed_step']]);
            $process->refresh();
            LoggerService::info('Completed step updated successfully', extra: [
                'process_id' => $process->id,
                'completed_step' => $response['completed_step'],
            ]);
        }
    }

    /**
     * Get step handler method name
     */
    private function getStepHandler(string $step): ?string
    {
        return $this->stepHandlers[$step] ?? null;
    }

    /**
     * Check if policy issuance automation is enabled
     */
    public function isPolicyIssuanceAutomationEnabled(): bool
    {
        return (bool) app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_ADNIC_HEALTH_POLICY_ISSUANCE);
    }

    /**
     * Check if policy issuance automation retry is enabled for timeout
     */
    public function isPolicyIssuanceAutomationRetryEnabledForTimeout(): bool
    {
        return (bool) app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_ADNIC_HEALTH_POLICY_ISSUANCE);
    }

    /**
     * Minutes to wait after the last policy issuance record update before dispatching a retry for TIMEOUT status.
     */
    public function getPolicyIssuanceTimeoutRetryCooldownMinutes(): int
    {
        return (int) app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ADNIC_POLICY_ISSUANCE_TIMEOUT_RETRY_COOLDOWN_MINUTES);
    }

    public function getAllowRetryForTimeout(): int
    {
        return (int) app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ADNIC_NUMBER_OF_ALLOWED_RETRY_FOR_TIMEOUT);
    }

    /**
     * When a policy issuance enters TIMEOUT, schedule a delayed job to reset it to PENDING for automation retry.
     * Delay and max retries come from application storage; the job re-validates flags before updating.
     */
    public function handleTimeoutStatusUpdate(PolicyIssuance $policyIssuance): void
    {
        LoggerService::info('ADNIC timeout status observed', [
            'policy_issuance_id' => $policyIssuance->id,
            'status' => $policyIssuance->status,
        ]);

        if (! $this->isPolicyIssuanceAutomationRetryEnabledForTimeout()) {
            LoggerService::info('ADNIC timeout retry not scheduled: retry automation disabled', [
                'policy_issuance_id' => $policyIssuance->id,
            ]);

            return;
        }

        $delayMinutes = max(0, $this->getPolicyIssuanceTimeoutRetryCooldownMinutes());

        PolicyIssuanceTimeoutRetryJob::dispatch($policyIssuance->id)
            ->delay(now()->addMinutes($delayMinutes));
    }

    /**
     * Create policy issuance schedule
     *
     * @param  mixed  $quote
     * @param  mixed  $insurer
     * @return void
     */
    public function createPolicyIssuanceSchedule($quote, $insurer)
    {
        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::ADNIC_HEALTH_POLICY_AUTOMATION);
        $isSTPCase = $quote->isSTPCase();
        LoggerService::info('Policy issuance schedule initiated', extra: [
            'insurer_id' => $insurer->id,
            'insurer_code' => $insurer->code,
            'automation_enabled' => $this->isPolicyIssuanceAutomationEnabled(),
            'isSTPCase' => $isSTPCase,
        ]);

        if ($this->isPolicyIssuanceAutomationEnabled() && $isSTPCase) {
            (new PolicyIssuanceService)->schedulePolicyIssuance($quote, $insurer, QuoteTypes::HEALTH->value, self::class);
        } else {
            LoggerService::warning('Automation is disabled', extra: [
                'feature' => LoggerFeatureEnum::ADNIC_HEALTH_POLICY_AUTOMATION->value,
            ]);
        }
    }

    /**
     * Execute automation steps
     *
     * @param  mixed  $process
     * @return array<string, mixed>
     */
    public function executeSteps($process): array
    {
        if (! $this->isPolicyIssuanceAutomationEnabled()) {
            LoggerService::warning('Automation is disabled', extra: [
                'process_id' => $process->id ?? null,
                'feature' => LoggerFeatureEnum::ADNIC_HEALTH_POLICY_AUTOMATION->value,
            ]);

            return [
                'status' => false,
                'error' => 'ADNIC Health Automation is disabled',
                'message' => 'ADNIC Health Automation is disabled',
            ];
        }

        $response = ['status' => false, 'error' => null, 'message' => null];

        $quote = $process->model;

        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::ADNIC_HEALTH_POLICY_AUTOMATION);
        LoggerService::info('Execution started', extra: [
            'process_id' => $process->id,
            'plan_id' => $quote->plan_id,
            'current_status' => $process->status,
            'completed_step' => $process->completed_step,
        ]);

        $shouldLogExecutionCompleted = true;

        try {
            $validationResult = $this->validationService->validateRequiredData($quote);
            if (! $validationResult['status']) {
                $response = $validationResult;
                $shouldLogExecutionCompleted = false;
            } else {
                $lastCompletedStep = $process->completed_step;
                $nextStepToBeExecuted = $lastCompletedStep ? $this->getNextStep($lastCompletedStep) : $this->getAPISteps()[0];

                LoggerService::info('Starting step sequence execution', extra: [
                    'process_id' => $process->id,
                    'last_completed_step' => $lastCompletedStep,
                    'next_step' => $nextStepToBeExecuted,
                ]);

                $executeStepSequence = $this->executeStepSequence($quote, $process, $nextStepToBeExecuted);

                $response['status'] = $executeStepSequence['status'];
                $response['message'] = $executeStepSequence['message'];
                $response['error'] = $executeStepSequence['error'];

                $stepMessage = $executeStepSequence['message'] ?? null;
                if (
                    is_string($stepMessage) &&
                    str_contains($stepMessage, AdnicEnum::POLICY_CONVERSION_ALREADY_IN_PROGRESS)
                ) {
                    $response['timeout'] = true;
                }
            }
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
            $shouldLogExecutionCompleted = false;
            LoggerService::error('Exception occurred during execution', extra: [
                'process_id' => $process->id,
                'quote_id' => $quote->id,
                'quote_type' => QuoteTypes::HEALTH->value,
                'quote_code' => $quote->code,
            ], exception: $e);
        }

        if ($shouldLogExecutionCompleted) {
            LoggerService::info('Execution completed', extra: [
                'process_id' => $process->id,
                'final_status' => $response['status'],
                'message' => $response['message'],
            ]);
        }

        return $response;
    }

    /**
     * Execute step sequence
     *
     * @param  mixed  $quote
     * @param  mixed  $process
     * @param  string  $nextStepToBeExecuted
     * @return array<string, mixed>
     */
    private function executeStepSequence($quote, $process, $nextStepToBeExecuted): array
    {
        $currentStep = $nextStepToBeExecuted;
        $allSteps = $this->getAPISteps();
        $stepsExecuted = [];
        $failureResponse = null;

        while ($currentStep !== null && $failureResponse === null) {
            if (! in_array($currentStep, $allSteps, true)) {
                $error = 'Unknown step encountered: '.$currentStep;
                LoggerService::error('Invalid step', extra: [
                    'process_id' => $process->id,
                    'current_step' => $currentStep,
                    'valid_steps' => $allSteps,
                    'steps_executed' => $stepsExecuted,
                ]);

                $failureResponse = $this->responseHandler->buildStepResponse($currentStep, false, null, $error);
                break;
            }

            $handler = $this->getStepHandler($currentStep);
            if (! $handler || ! method_exists($this->stepExecutor, $handler)) {
                $error = 'Missing handler for step: '.$currentStep;
                LoggerService::error('Handler not found for step', extra: [
                    'process_id' => $process->id,
                    'current_step' => $currentStep,
                    'expected_handler' => $handler,
                    'steps_executed' => $stepsExecuted,
                ]);

                $failureResponse = $this->responseHandler->buildStepResponse($currentStep, false, null, $error);
                break;
            }

            LoggerService::info('Executing step', extra: [
                'step' => $currentStep,
                'handler' => $handler,
                'process_id' => $process->id,
            ]);

            $response = $this->stepExecutor->{$handler}($quote, $process);
            $stepsExecuted[] = $currentStep;

            if (isset($response['status']) && ! $response['status']) {
                LoggerService::error('Step execution failed', extra: [
                    'process_id' => $process->id,
                    'failed_step' => $currentStep,
                    'steps_executed' => $stepsExecuted,
                    'error' => $response['error'] ?? AdnicEnum::UNKNOWN_ERROR,
                ]);

                $failureResponse = $response;
                break;
            }

            $this->updateProcessWithStep($process, $response);
            $currentStep = $this->getNextStep($process->completed_step);
        }

        if ($failureResponse !== null) {
            return $failureResponse;
        }

        LoggerService::info('All steps completed successfully', extra: [
            'process_id' => $process->id,
            'steps_executed' => $stepsExecuted,
            'total_steps' => count($stepsExecuted),
        ]);

        $lastCompletedStep = $process->completed_step;
        if ($lastCompletedStep === null && $stepsExecuted !== []) {
            $lastCompletedStep = $stepsExecuted[array_key_last($stepsExecuted)];
        }

        return [
            'status' => true,
            'message' => 'All policy issuance steps completed successfully',
            'completed_step' => $lastCompletedStep,
            'error' => null,
        ];
    }

    /**
     * Get next step to execute
     *
     * @param  string|null  $completedStep
     */
    public function getNextStep($completedStep = null): ?string
    {
        $allSteps = $this->getAPISteps();
        $nextStep = null;

        if (! $completedStep) {
            $nextStep = $allSteps[0];
            LoggerService::info('No previous step, starting from beginning', extra: [
                'next_step' => $nextStep,
            ]);
        } else {
            $completedStepIndex = array_search($completedStep, $allSteps, true);
            if ($completedStepIndex === false) {
                LoggerService::warning('Completed step not found in valid steps', extra: [
                    'completed_step' => $completedStep,
                    'valid_steps' => $allSteps,
                ]);
            } elseif ($completedStepIndex === count($allSteps) - 1) {
                LoggerService::info('All steps completed', extra: [
                    'last_completed_step' => $completedStep,
                ]);
            } else {
                $nextStep = $allSteps[$completedStepIndex + 1];
                LoggerService::info('Next step determined', extra: [
                    'completed_step' => $completedStep,
                    'next_step' => $nextStep,
                ]);
            }
        }

        return $nextStep;
    }

    /**
     * Map the next automation step (after the last completed step) to an insurer API failure status for stuck/failed flows.
     */
    public function getInsurerAPIStatusByStep($policyIssuance): ?int
    {
        $lastCompletedStep = $policyIssuance->completed_step;
        $step = $this->getNextStep($lastCompletedStep);
        $insurerApiStatus = [
            AdnicEnum::STEP_UPLOAD_DOCUMENTS => PolicyIssuanceEnum::PIA_UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID,
            AdnicEnum::STEP_ISSUE_POLICY => PolicyIssuanceEnum::PIA_POLICY_ISSUANCE_API_FAILED_STATUS_ID,
            AdnicEnum::STEP_UPLOAD_POLICY_DOCS => PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID,
        ];

        return $insurerApiStatus[$step] ?? null;
    }

    /**
     * Get steps locking status
     *
     * @param  mixed  $quote
     * @param  bool  $throughAutomation
     */
    public function getStepsLockingStatus($quote, $throughAutomation = false): array
    {
        return $this->bookPolicyService->getStepsLockingStatus($quote, $throughAutomation);
    }

    public function retryPolicyIssuance($policyIssuance)
    {
        LoggerService::info('Retry Policy Issuance', extra: [
            'policyIssuance' => $policyIssuance,
        ]);

        if (
            in_array($policyIssuance->status, [
                PolicyIssuanceEnum::FAILED_STATUS, PolicyIssuanceEnum::TIMEOUT_STATUS,
            ]) &&
            is_null($policyIssuance->completed_step) // it means first step document upload.
        ) {
            $policyIssuance->update([
                'status' => PolicyIssuanceEnum::PENDING_STATUS,
            ]);
        }
    }
}
