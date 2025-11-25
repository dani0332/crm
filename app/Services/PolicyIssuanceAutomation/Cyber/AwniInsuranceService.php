<?php

namespace App\Services\PolicyIssuanceAutomation\Cyber;

use App\Enums\ApplicationStorageEnums;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Interfaces\PolicyIssuanceInterface;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Exception;

class AwniInsuranceService implements PolicyIssuanceInterface
{
    private string $className = 'awniInsuranceService';
    public mixed $policyIssuance = null;

    public const TYPE = quoteTypeCode::CYBER;
    public const TYPE_ID = QuoteTypeId::Cyber;

    private array $stepHandlers = [];

    public const UPLOAD_DOCUMENTS = 'UploadDocuments';
    public const ISSUE_POLICY = 'IssuePolicy';
    public const UPLOAD_POLICY_DOCUMENTS_TO_IMCRM = 'UploadPolicyDocumentsToIMCRM';
    public const BOOK_POLICY = 'BookPolicy';

    public function __construct(
        private AwnicStepExecutor $stepExecutor,
        private AwnicValidationService $validationService,
        private AwnicBookPolicyService $bookPolicyService,
        private AwnicResponseHandler $responseHandler,
    ) {
        $this->stepHandlers = [
            self::ISSUE_POLICY => 'executeIssuePolicyStep',
            self::UPLOAD_DOCUMENTS => 'executeUploadDocumentsStep',
            self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM => 'executeUploadPolicyDocumentsStep',
            self::BOOK_POLICY => 'executeBookPolicyStep',
        ];
    }

    /**
     * Get API steps in order
     *
     * @return array
     */
    private function getAPISteps(): array
    {
        return [
            self::ISSUE_POLICY,
            self::UPLOAD_DOCUMENTS,
            self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
            self::BOOK_POLICY,
        ];
    }

    /**
     * Update process with completed step
     *
     * @param mixed $process
     * @param array $response
     * @return void
     */
    private function updateProcessWithStep($process, array $response): void
    {
        if (($response['status'] ?? false) && isset($response['completed_step'])) {
            $process->update(['completed_step' => $response['completed_step']]);
            $process->refresh();
            LoggerService::info('Completed step updated successfully', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
                'process_id' => $process->id,
                'completed_step' => $response['completed_step'],
            ]);
        }
    }

    /**
     * Get step handler method name
     *
     * @param string $step
     * @return string|null
     */
    private function getStepHandler(string $step): ?string
    {
        return $this->stepHandlers[$step] ?? null;
    }

    /**
     * Check if policy issuance automation is enabled
     *
     * @return bool
     */
    public function isPolicyIssuanceAutomationEnabled(): bool
    {
        return app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_AWNI_CYBER_POLICY_ISSUANCE);
    }

    /**
     * Check if policy issuance automation retry is enabled for timeout
     *
     * @return bool
     */
    public function isPolicyIssuanceAutomationRetryEnabledForTimeout(): bool
    {
        return (bool) app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_AWNI_CYBER_POLICY_ISSUANCE);
    }

    /**
     * Create policy issuance schedule
     *
     * @param mixed $quote
     * @param mixed $insurer
     * @return void
     */
    public function createPolicyIssuanceSchedule($quote, $insurer)
    {
        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::AWNIC_CYBER_POLICY_AUTOMATION);
        LoggerService::info('Policy issuance schedule initiated', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'insurer_id' => $insurer->id,
            'insurer_code' => $insurer->code,
            'automation_enabled' => $this->isPolicyIssuanceAutomationEnabled(),
        ]);

        if ($this->isPolicyIssuanceAutomationEnabled()) {
            $this->policyIssuance = (new PolicyIssuanceService)->schedulePolicyIssuance($quote, $insurer, self::TYPE, $this->className);
        } else {
            LoggerService::warning('Automation is disabled', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
                'feature' => LoggerFeatureEnum::AWNIC_CYBER_POLICY_AUTOMATION->value,
            ]);
        }
    }

    /**
     * Execute automation steps
     *
     * @param mixed $process
     * @return array
     */
    public function executeSteps($process)
    {
        $response = ['status' => false, 'error' => null, 'message' => null];

        $this->policyIssuance = $process;
        $quote = $process->model;

        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::AWNIC_CYBER_POLICY_AUTOMATION);
        LoggerService::info('Execution started', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'process_id' => $process->id,
            'plan_id' => $quote->plan_id,
            'current_status' => $process->status,
            'completed_step' => $process->completed_step,
        ]);

        try {
            if (! $this->isPolicyIssuanceAutomationEnabled()) {
                LoggerService::warning('Automation is disabled', extra: [
                    'class' => $this->className,
                    'function' => __FUNCTION__,
                    'feature' => LoggerFeatureEnum::AWNIC_CYBER_POLICY_AUTOMATION->value,
                ]);
                $response['error'] = 'AWNI Cyber Automation is disabled';
                $response['message'] = 'AWNI Cyber Automation is disabled';

                return $response;
            }

            $validationResult = $this->validationService->validateRequiredData($quote);
            if (!$validationResult['status']) {
                return $validationResult;
            }

            $lastCompletedStep = $process->completed_step;
            $nextStepToBeExecuted = $lastCompletedStep ? $this->getNextStep($lastCompletedStep) : $this->getAPISteps()[0];
            
            LoggerService::info('Starting step sequence execution', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
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
                'class' => $this->className,
                'function' => __FUNCTION__,
                'process_id' => $process->id,
            ], exception: $e);

            return $response;
        }

        LoggerService::info('Execution completed', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'process_id' => $process->id,
            'final_status' => $response['status'],
            'message' => $response['message'],
        ]);

        return $response;
    }

    /**
     * Execute step sequence
     *
     * @param mixed $quote
     * @param mixed $process
     * @param string $nextStepToBeExecuted
     * @return array
     */
    private function executeStepSequence($quote, $process, $nextStepToBeExecuted)
    {
        $currentStep = $nextStepToBeExecuted;
        $allSteps = $this->getAPISteps();
        $stepsExecuted = [];

        while ($currentStep !== null) {
            if (! in_array($currentStep, $allSteps, true)) {
                $message = 'Unknown step encountered: ' . $currentStep;
                LoggerService::error('Invalid step', extra: [
                    'class' => $this->className,
                    'function' => __FUNCTION__,
                    'current_step' => $currentStep,
                    'valid_steps' => $allSteps,
                    'steps_executed' => $stepsExecuted,
                ]);

                return $this->responseHandler->buildStepResponse($currentStep, false, $message, $message);
            }

            $handler = $this->getStepHandler($currentStep);
            if (! $handler || ! method_exists($this->stepExecutor, $handler)) {
                $message = 'Missing handler for step: ' . $currentStep;
                LoggerService::error('Handler not found for step', extra: [
                    'class' => $this->className,
                    'function' => __FUNCTION__,
                    'current_step' => $currentStep,
                    'expected_handler' => $handler,
                    'steps_executed' => $stepsExecuted,
                ]);

                return $this->responseHandler->buildStepResponse($currentStep, false, $message, $message);
            }

            LoggerService::info('Executing step', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
                'step' => $currentStep,
                'handler' => $handler,
                'process_id' => $process->id,
            ]);

            $response = $this->stepExecutor->{$handler}($quote, $process);
            $stepsExecuted[] = $currentStep;

            if (isset($response['status']) && ! $response['status']) {
                LoggerService::error('Step execution failed', extra: [
                    'class' => $this->className,
                    'function' => __FUNCTION__,
                    'failed_step' => $currentStep,
                    'steps_executed' => $stepsExecuted,
                    'error' => $response['error'] ?? 'Unknown error',
                ]);
                return $response;
            }

            $this->updateProcessWithStep($process, $response);
            $currentStep = $this->getNextStep($process->completed_step);
        }

        LoggerService::info('All steps completed successfully', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'steps_executed' => $stepsExecuted,
            'total_steps' => count($stepsExecuted),
        ]);

        return [
            'status' => true,
            'message' => 'All policy issuance steps completed successfully',
            'completed_step' => $currentStep,
            'error' => null,
        ];
    }

    /**
     * Get next step to execute
     *
     * @param string|null $completedStep
     * @return string|null
     */
    public function getNextStep($completedStep = null): ?string
    {
        $allSteps = $this->getAPISteps();

        if (! $completedStep) {
            $nextStep = $allSteps[0];
            LoggerService::info('No previous step, starting from beginning', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
                'next_step' => $nextStep,
            ]);
            return $nextStep;
        }

        $completedStepIndex = array_search($completedStep, $allSteps);
        if ($completedStepIndex === false) {
            LoggerService::warning('Completed step not found in valid steps', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
                'completed_step' => $completedStep,
                'valid_steps' => $allSteps,
            ]);
            return null;
        }

        if ($completedStepIndex === count($allSteps) - 1) {
            LoggerService::info('All steps completed', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
                'last_completed_step' => $completedStep,
            ]);
            return null;
        }

        $nextStep = $allSteps[$completedStepIndex + 1];
        LoggerService::info('Next step determined', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'completed_step' => $completedStep,
            'next_step' => $nextStep,
        ]);

        return $nextStep;
    }

    /**
     * Get steps locking status
     *
     * @param mixed $quote
     * @param bool $throughAutomation
     * @return array
     */
    public function getStepsLockingStatus($quote, $throughAutomation = false): array
    {
        return $this->bookPolicyService->getStepsLockingStatus($quote, $throughAutomation);
    }
}
