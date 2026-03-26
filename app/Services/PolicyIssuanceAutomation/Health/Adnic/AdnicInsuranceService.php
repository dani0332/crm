<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Health\Adnic;

use App\Enums\AdnicEnum;
use App\Enums\ApplicationStorageEnums;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Interfaces\PolicyIssuanceInterface;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Exception;

class AdnicInsuranceService implements PolicyIssuanceInterface
{
    private array $stepHandlers = [
        AdnicEnum::STEP_ISSUE_POLICY => 'executeIssuePolicyStep',
        AdnicEnum::STEP_UPLOAD_DOCUMENTS => 'executeUploadDocumentsStep',
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
     * @return array
     */
    public function executeSteps($process)
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

        try {
            $validationResult = $this->validationService->validateRequiredData($quote);
            if (! $validationResult['status']) {
                return $validationResult;
            }

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
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
            LoggerService::error('Exception occurred during execution', extra: [
                'process_id' => $process->id,
                'quote_id' => $quote->id,
                'quote_type' => QuoteTypes::HEALTH->value,
                'quote_code' => $quote->code,
            ], exception: $e);

            return $response;
        }

        LoggerService::info('Execution completed', extra: [
            'process_id' => $process->id,
            'final_status' => $response['status'],
            'message' => $response['message'],
        ]);

        return $response;
    }

    /**
     * Execute step sequence
     *
     * @param  mixed  $quote
     * @param  mixed  $process
     * @param  string  $nextStepToBeExecuted
     * @return array
     */
    private function executeStepSequence($quote, $process, $nextStepToBeExecuted)
    {
        $currentStep = $nextStepToBeExecuted;
        $allSteps = $this->getAPISteps();
        $stepsExecuted = [];

        while ($currentStep !== null) {
            if (! in_array($currentStep, $allSteps, true)) {
                $error = 'Unknown step encountered: '.$currentStep;
                LoggerService::error('Invalid step', extra: [
                    'process_id' => $process->id,
                    'current_step' => $currentStep,
                    'valid_steps' => $allSteps,
                    'steps_executed' => $stepsExecuted,
                ]);

                return $this->responseHandler->buildStepResponse($currentStep, false, null, $error);
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

                return $this->responseHandler->buildStepResponse($currentStep, false, null, $error);
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

                return $response;
            }

            $this->updateProcessWithStep($process, $response);
            $currentStep = $this->getNextStep($process->completed_step);
        }

        LoggerService::info('All steps completed successfully', extra: [
            'process_id' => $process->id,
            'steps_executed' => $stepsExecuted,
            'total_steps' => count($stepsExecuted),
        ]);

        return [
            'status' => true,
            'message' => 'All policy issuance steps completed successfully',
            'completed_step' => $process->completed_step ?? end($stepsExecuted) ?: null,
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

        if (! $completedStep) {
            $nextStep = $allSteps[0];
            LoggerService::info('No previous step, starting from beginning', extra: [
                'next_step' => $nextStep,
            ]);

            return $nextStep;
        }

        $completedStepIndex = array_search($completedStep, $allSteps);
        if ($completedStepIndex === false) {
            LoggerService::warning('Completed step not found in valid steps', extra: [
                'completed_step' => $completedStep,
                'valid_steps' => $allSteps,
            ]);

            return null;
        }

        if ($completedStepIndex === count($allSteps) - 1) {
            LoggerService::info('All steps completed', extra: [
                'last_completed_step' => $completedStep,
            ]);

            return null;
        }

        $nextStep = $allSteps[$completedStepIndex + 1];
        LoggerService::info('Next step determined', extra: [
            'completed_step' => $completedStep,
            'next_step' => $nextStep,
        ]);

        return $nextStep;
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
