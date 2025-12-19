<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance;

use App\Enums\ApplicationStorageEnums;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\NgiEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Interfaces\PolicyIssuanceInterface;
use App\Jobs\SendBookPolicyDocumentsJob;
use App\Services\ApplicationStorageService;
use App\Services\DeviceFailureEmailService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Exception;

class NgiInsuranceService implements PolicyIssuanceInterface
{
    private array $stepHandlers = [
        NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE => 'executeCreatePolicyFromQuoteStep',
        NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM => 'executeGetPolicyDocumentsAndUploadToIMCRMStep',
        NgiEnum::STEP_BOOK_POLICY => 'executeBookPolicyStep',
    ];

    public function __construct(
        private NgiStepExecutor $stepExecutor,
        private NgiValidationService $validationService,
        private NgiBookPolicyService $bookPolicyService,
        private NgiResponseHandler $responseHandler,
    ) {}

    /**
     * Get API steps in order
     */
    private function getAPISteps(): array
    {
        return [
            NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
            NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
            NgiEnum::STEP_BOOK_POLICY,
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
        return (bool) app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_NGI_SMARTPHONE_POLICY_ISSUANCE);
    }

    /**
     * Check if policy issuance automation retry is enabled for timeout
     */
    public function isPolicyIssuanceAutomationRetryEnabledForTimeout(): bool
    {
        return (bool) app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_NGI_SMARTPHONE_POLICY_ISSUANCE);
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
        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::NGI_SMARTPHONE_POLICY_AUTOMATION);
        LoggerService::info('Policy issuance schedule initiated for Device/Smartphone', extra: [
            'insurer_id' => $insurer->id,
            'insurer_code' => $insurer->code,
            'automation_enabled' => $this->isPolicyIssuanceAutomationEnabled(),
        ]);

        if ($this->isPolicyIssuanceAutomationEnabled()) {
            (new PolicyIssuanceService)->schedulePolicyIssuance($quote, $insurer, QuoteTypes::DEVICE->value, self::class);
        } else {
            LoggerService::warning('Automation is disabled', extra: [
                'feature' => LoggerFeatureEnum::NGI_SMARTPHONE_POLICY_AUTOMATION->value,
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

        if (! $this->isPolicyIssuanceAutomationEnabled()) {
            LoggerService::warning('Automation is disabled', extra: [
                'process_id' => $process->id ?? null,
                'feature' => LoggerFeatureEnum::NGI_SMARTPHONE_POLICY_AUTOMATION->value,
            ]);
            $response['error'] = 'NGI Smartphone Automation is disabled';
            $response['message'] = 'NGI Smartphone Automation is disabled';
        } else {
            $quote = $process->model;

            LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::NGI_SMARTPHONE_POLICY_AUTOMATION);
            LoggerService::info('Execution started for Device/Smartphone', extra: [
                'process_id' => $process->id,
                'plan_id' => $quote->plan_id,
                'insurer_quote_number' => $quote->insurer_quote_number,
                'current_status' => $process->status,
                'completed_step' => $process->completed_step,
            ]);

            try {
                $customer = $quote->customer ?? null;
                $deviceQuote = $quote->deviceQuote ?? null;
                $latestInsured = $quote?->latestInsured ?? null;
                $validationResult = $this->validationService->validateRequiredData($quote, $customer, $deviceQuote, $latestInsured);

                if ($validationResult['status']) {
                    $lastCompletedStep = $process->completed_step;
                    $nextStepToBeExecuted = $lastCompletedStep ? $this->getNextStep($lastCompletedStep) : $this->getAPISteps()[0];

                    LoggerService::info('Starting step sequence execution', extra: [
                        'process_id' => $process->id,
                        'last_completed_step' => $lastCompletedStep,
                        'next_step' => $nextStepToBeExecuted,
                    ]);

                    $executeStepSequence = $this->executeStepSequence($quote, $process, $nextStepToBeExecuted);
                    if (isset($executeStepSequence['documents_pending']) && $executeStepSequence['documents_pending']) {
                        $response['documents_pending'] = $executeStepSequence['documents_pending'];
                    }
                    $response['status'] = $executeStepSequence['status'];
                    $response['message'] = $executeStepSequence['message'];
                    $response['error'] = $executeStepSequence['error'];
                } else {
                    $response = $validationResult;
                }
            } catch (Exception $e) {
                $response['error'] = $e->getMessage();
                LoggerService::error('Exception occurred during execution', extra: [
                    'process_id' => $process->id,
                    'quote_id' => $quote->id,
                    'quote_type' => QuoteTypes::DEVICE->value,
                    'quote_code' => $quote->code,
                ], exception: $e);
            }

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
     * @return array
     */
    private function executeStepSequence($quote, $process, $nextStepToBeExecuted)
    {
        $currentStep = $nextStepToBeExecuted;
        $allSteps = $this->getAPISteps();
        $stepsExecuted = [];
        $result = [
            'status' => true,
            'message' => 'All policy issuance steps completed successfully',
            'completed_step' => null,
            'error' => null,
        ];

        while ($currentStep !== null) {
            if (! in_array($currentStep, $allSteps, true)) {
                $error = 'Unknown step encountered: '.$currentStep;
                LoggerService::error('Invalid step', extra: [
                    'process_id' => $process->id,
                    'current_step' => $currentStep,
                    'valid_steps' => $allSteps,
                    'steps_executed' => $stepsExecuted,
                ]);
                $result = $this->responseHandler->buildStepResponse($currentStep, false, null, $error);
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
                $result = $this->responseHandler->buildStepResponse($currentStep, false, null, $error);
                break;
            }

            LoggerService::info('Executing step', extra: [
                'step' => $currentStep,
                'handler' => $handler,
                'process_id' => $process->id,
            ]);

            $response = $this->stepExecutor->{$handler}($quote, $process);
            $stepsExecuted[] = $currentStep;

            if (isset($response['documents_pending']) && $response['documents_pending']) {
                LoggerService::info('Step dispatched async job, setting documents_pending status and exiting', extra: [
                    'process_id' => $process->id,
                    'current_step' => $currentStep,
                    'steps_executed' => $stepsExecuted,
                ]);
                $result = [
                    'status' => true,
                    'documents_pending' => true,
                    'message' => $response['message'] ?? 'Document retrieval job dispatched',
                    'completed_step' => $response['completed_step'] ?? $currentStep,
                    'error' => null,
                ];
                break;
            }

            if (isset($response['status']) && ! $response['status']) {
                LoggerService::error('Step execution failed', extra: [
                    'process_id' => $process->id,
                    'failed_step' => $currentStep,
                    'steps_executed' => $stepsExecuted,
                    'error' => $response['error'] ?? NgiEnum::UNKNOWN_ERROR,
                ]);
                $result = $response;
                break;
            }

            $this->updateProcessWithStep($process, $response);
            $currentStep = $this->getNextStep($process->completed_step);
        }

        if ($result['status'] && ! isset($result['documents_pending'])) {
            LoggerService::info('All steps completed successfully', extra: [
                'process_id' => $process->id,
                'steps_executed' => $stepsExecuted,
                'total_steps' => count($stepsExecuted),
            ]);
            $result['completed_step'] = $currentStep;
        }

        return $result;
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
                $nextStep = null;
            } elseif ($completedStepIndex === count($allSteps) - 1) {
                LoggerService::info('All steps completed', extra: [
                    'last_completed_step' => $completedStep,
                ]);
                $nextStep = null;
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
     * Get steps locking status
     *
     * @param  mixed  $quote
     * @param  bool  $throughAutomation
     */
    public function getStepsLockingStatus($quote, $throughAutomation = false): array
    {
        return $this->bookPolicyService->getStepsLockingStatus($quote, $throughAutomation);
    }

    /**
     * Get insurer API status by step (required for markPolicyIssuanceFailed)
     *
     * @param  mixed  $policyIssuance
     */
    public function getInsurerAPIStatusByStep($policyIssuance): ?int
    {
        $lastCompletedStep = $policyIssuance->completed_step;
        $step = $this->getNextStep($lastCompletedStep);

        $insurerApiStatus = [
            NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE => PolicyIssuanceEnum::PIA_POLICY_ISSUANCE_API_FAILED_STATUS_ID,
            NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM => PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID,
            NgiEnum::STEP_BOOK_POLICY => PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID,
        ];

        return $insurerApiStatus[$step] ?? null;
    }

    /**
     * Update quote API issuance status and allocate lead
     * Called by SageApiService after book policy completes
     *
     * @param  mixed  $quote
     * @param  int|null  $newInsurerApiStatus
     * @param  int|null  $newApiIssuanceStatus
     */
    public function updateQuoteApiIssuanceStatusAndAllocate($quote, $newInsurerApiStatus = null, $newApiIssuanceStatus = null): void
    {
        // TODO:: NGI:: need to confirm this function with Bilal we might need some changes in this function or sub function calls made from here written in this file.
        LoggerService::info('Starting updateQuoteApiIssuanceStatusAndAllocate for Device/Smartphone', extra: [
            'quote_code' => $quote->code,
        ]);

        $policyIssuanceAutomation = $quote->policyIssuance;
        $isPolicyBooked = $quote->quote_status_id === QuoteStatusEnum::PolicyBooked;
        $isPolicyBookingFailed = $quote->quote_status_id === QuoteStatusEnum::POLICY_BOOKING_FAILED;

        $isPolicyAutomationStatusCompleted = $policyIssuanceAutomation?->status == PolicyIssuanceEnum::COMPLETED_STATUS;
        $insurerApiStatus = $quote?->insurer_api_status;
        $apiIssuanceStatus = $quote?->api_issuance_status;

        $isInsurerApiStatusAlreadyFailed = $quote->isBookingFailed() || $quote->isPolicyIssuanceFailed();

        LoggerService::info('Current status check', extra: [
            'quote_code' => $quote->code,
            'isInsurerApiStatusAlreadyFailed' => $isInsurerApiStatusAlreadyFailed,
        ]);

        // If Quote API issuance status is not already set, then set insurer api and api issuance status
        if (! $apiIssuanceStatus) {
            if ($isPolicyAutomationStatusCompleted && $isPolicyBooked && ! $insurerApiStatus) {
                $newApiIssuanceStatus = PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID;
            } elseif ($isPolicyAutomationStatusCompleted && $isPolicyBookingFailed) {
                if (! $insurerApiStatus) {
                    $newInsurerApiStatus = PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID;
                }
                $newApiIssuanceStatus = PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID;
            }
        }

        LoggerService::info('Status determination complete', extra: [
            'quote_code' => $quote->code,
            'insurerApiStatus' => $insurerApiStatus,
            'apiIssuanceStatus' => $apiIssuanceStatus,
            'isPolicyAutomationStatusCompleted' => $isPolicyAutomationStatusCompleted,
            'isPolicyBooked' => $isPolicyBooked,
            'isPolicyBookingFailed' => $isPolicyBookingFailed,
            'newInsurerApiStatus' => $newInsurerApiStatus,
            'newApiIssuanceStatus' => $newApiIssuanceStatus,
        ]);

        // Update statuses
        $this->updateQuoteInsurerApiStatus($quote, $newInsurerApiStatus);
        $this->updateQuoteApiIssuanceStatus($quote, $newApiIssuanceStatus);

        // Allocate lead and send notifications
        $this->allocateLead($quote, $isInsurerApiStatusAlreadyFailed);

        LoggerService::info('updateQuoteApiIssuanceStatusAndAllocate completed', extra: [
            'quote_code' => $quote->code,
        ]);
    }

    /**
     * Update quote insurer API status
     *
     * @param  mixed  $quote
     * @param  int|null  $newInsurerApiStatus
     */
    private function updateQuoteInsurerApiStatus($quote, $newInsurerApiStatus): void
    {
        LoggerService::info('Updating quote insurer API status', extra: [
            'quote_code' => $quote->code,
            'new_status' => $newInsurerApiStatus,
        ]);

        if ($newInsurerApiStatus) {
            $quote->update(['insurer_api_status_id' => $newInsurerApiStatus]);
        }
    }

    /**
     * Update quote API issuance status
     *
     * @param  mixed  $quote
     * @param  int|null  $newApiIssuanceStatus
     */
    private function updateQuoteApiIssuanceStatus($quote, $newApiIssuanceStatus): void
    {
        LoggerService::info('Updating quote API issuance status', extra: [
            'quote_code' => $quote->code,
            'new_status' => $newApiIssuanceStatus,
        ]);

        if ($newApiIssuanceStatus) {
            $quote->update(['api_issuance_status_id' => $newApiIssuanceStatus]);
        }
    }

    /**
     * Allocate lead and send notifications after policy booking
     *
     * @param  mixed  $quote
     * @param  bool  $isInsurerApiStatusAlreadyFailed
     */
    private function allocateLead($quote, $isInsurerApiStatusAlreadyFailed): void
    {
        LoggerService::info('Allocating lead for Device/Smartphone', extra: [
            'quote_code' => $quote->code,
        ]);

        $advisorId = $quote?->advisor_id;
        $isPolicyBooked = $quote->quote_status_id === QuoteStatusEnum::PolicyBooked;

        LoggerService::info('Advisor assignment check', extra: [
            'quote_code' => $quote->code,
            'advisorId' => $advisorId,
        ]);

        if ($advisorId) {
            // Send failure notification if not already failed
            if (! $isInsurerApiStatusAlreadyFailed && $quote?->insurer_api_status != null) {
                // Uses service method that handles failure type determination
                app(DeviceFailureEmailService::class)->sendFailureEmailFromStatus(
                    $quote->id,
                    $quote->insurer_api_status_id
                );

                LoggerService::info('Automation failed - failure notification dispatched', extra: [
                    'quote_code' => $quote->code,
                    'insurer_api_status' => $quote->insurer_api_status_id,
                ]);
            }

            // Send policy documents if policy is booked
            if ($isPolicyBooked) {
                $data = new \stdClass;
                $data->model_type = QuoteTypes::DEVICE->value;
                $data->quote_id = $quote->id;

                LoggerService::info('Dispatching SendBookPolicyDocumentsJob', extra: [
                    'quote_code' => $quote->code,
                    'advisor_id' => $advisorId,
                ]);

                SendBookPolicyDocumentsJob::dispatch($data, $quote->code);
            }
        }
    }
}
