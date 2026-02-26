<?php

namespace App\Services\PolicyIssuanceAutomation\Cyber;

use App\Enums\ApplicationStorageEnums;
use App\Enums\AwnicEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTypes;
use App\Interfaces\PolicyIssuanceInterface;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Exception;

class AwnicInsuranceService implements PolicyIssuanceInterface
{
    private array $stepHandlers = [
        AwnicEnum::STEP_ISSUE_POLICY => 'executeIssuePolicyStep',
        AwnicEnum::STEP_UPLOAD_DOCUMENTS => 'executeUploadDocumentsStep',
        AwnicEnum::STEP_UPLOAD_POLICY_DOCS => 'executeUploadPolicyDocumentsStep',
        AwnicEnum::STEP_BOOK_POLICY => 'executeBookPolicyStep',
    ];

    public function __construct(
        private AwnicStepExecutor $stepExecutor,
        private AwnicValidationService $validationService,
        private AwnicBookPolicyService $bookPolicyService,
        private AwnicResponseHandler $responseHandler,
    ) {}

    /**
     * Get API steps in order
     */
    private function getAPISteps(): array
    {
        return [
            AwnicEnum::STEP_ISSUE_POLICY,
            AwnicEnum::STEP_UPLOAD_DOCUMENTS,
            AwnicEnum::STEP_UPLOAD_POLICY_DOCS,
            AwnicEnum::STEP_BOOK_POLICY,
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
        return app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_AWNI_CYBER_POLICY_ISSUANCE);
    }

    /**
     * Check if policy issuance automation retry is enabled for timeout
     */
    public function isPolicyIssuanceAutomationRetryEnabledForTimeout(): bool
    {
        return (bool) app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_AWNI_CYBER_POLICY_ISSUANCE);
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
        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::AWNIC_CYBER_POLICY_AUTOMATION);
        LoggerService::info('Policy issuance schedule initiated', extra: [
            'insurer_id' => $insurer->id,
            'insurer_code' => $insurer->code,
            'automation_enabled' => $this->isPolicyIssuanceAutomationEnabled(),
        ]);

        if ($this->isPolicyIssuanceAutomationEnabled()) {
            (new PolicyIssuanceService)->schedulePolicyIssuance($quote, $insurer, QuoteTypes::CYBER->value, self::class);
        } else {
            LoggerService::warning('Automation is disabled', extra: [
                'feature' => LoggerFeatureEnum::AWNIC_CYBER_POLICY_AUTOMATION->value,
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
        $response = ['status' => false, 'error' => null, 'message' => null];
        $shouldExecute = true;

        if (! $this->isPolicyIssuanceAutomationEnabled()) {
            LoggerService::warning('Automation is disabled', extra: [
                'process_id' => $process->id ?? null,
                'feature' => LoggerFeatureEnum::AWNIC_CYBER_POLICY_AUTOMATION->value,
            ]);
            $response['error'] = 'AWNI Cyber Automation is disabled';
            $response['message'] = 'AWNI Cyber Automation is disabled';
            $shouldExecute = false;
        }

        $quote = $process->model ?? null;

        if ($shouldExecute && $quote) {
            LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::AWNIC_CYBER_POLICY_AUTOMATION);
            LoggerService::info('Execution started', extra: [
                'process_id' => $process->id,
                'plan_id' => $quote->plan_id,
                'current_status' => $process->status,
                'completed_step' => $process->completed_step,
            ]);

            try {
                $validationResult = $this->validationService->validateRequiredData($quote);
                if (! $validationResult['status']) {
                    $response = $validationResult;
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
                }
            } catch (Exception $e) {
                $response['error'] = $e->getMessage();
                LoggerService::error('Exception occurred during execution', extra: [
                    'process_id' => $process->id ?? null,
                    'quote_id' => $quote->id ?? null,
                    'quote_type' => QuoteTypes::CYBER->value,
                    'quote_code' => $quote->code ?? null,
                ], exception: $e);
            }

            LoggerService::info('Execution completed', extra: [
                'process_id' => $process->id ?? null,
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
     * @return array
     */
    private function executeStepSequence($quote, $process, $nextStepToBeExecuted)
    {
        $currentStep = $nextStepToBeExecuted;
        $allSteps = $this->getAPISteps();
        $stepsExecuted = [];
        $finalResponse = null;

        while ($currentStep !== null) {
            if (! in_array($currentStep, $allSteps, true)) {
                $error = 'Unknown step encountered: '.$currentStep;
                LoggerService::error('Invalid step', extra: [
                    'process_id' => $process->id,
                    'current_step' => $currentStep,
                    'valid_steps' => $allSteps,
                    'steps_executed' => $stepsExecuted,
                ]);

                $finalResponse = $this->responseHandler->buildStepResponse($currentStep, false, null, $error);
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

                $finalResponse = $this->responseHandler->buildStepResponse($currentStep, false, null, $error);
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
                    'error' => $response['error'] ?? AwnicEnum::UNKNOWN_ERROR,
                ]);

                $finalResponse = $response;
                break;
            }

            $this->updateProcessWithStep($process, $response);
            $currentStep = $this->getNextStep($process->completed_step);
        }

        if (! $finalResponse) {
            LoggerService::info('All steps completed successfully', extra: [
                'process_id' => $process->id,
                'steps_executed' => $stepsExecuted,
                'total_steps' => count($stepsExecuted),
            ]);

            $finalResponse = [
                'status' => true,
                'message' => 'All policy issuance steps completed successfully',
                'completed_step' => $currentStep,
                'error' => null,
            ];
        }

        return $finalResponse;
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
            $completedStepIndex = array_search($completedStep, $allSteps);
            if ($completedStepIndex === false) {
                LoggerService::warning('Completed step not found in valid steps', extra: [
                    'completed_step' => $completedStep,
                    'valid_steps' => $allSteps,
                ]);
            } elseif ($completedStepIndex !== count($allSteps) - 1) {
                $nextStep = $allSteps[$completedStepIndex + 1];
                LoggerService::info('Next step determined', extra: [
                    'completed_step' => $completedStep,
                    'next_step' => $nextStep,
                ]);
            } else {
                LoggerService::info('All steps completed', extra: [
                    'last_completed_step' => $completedStep,
                ]);
            }
        }

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
}
