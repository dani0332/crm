<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Travel\Dic;

use App\Enums\ApplicationStorageEnums;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Interfaces\PolicyIssuanceInterface;
use App\Models\PolicyIssuance;
use App\Models\TravelQuote;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Exception;

class DicInsuranceService implements PolicyIssuanceInterface
{
    private const MISSING_HANDLER_MESSAGE_PREFIX = 'Missing handler for step: ';
    public const TYPE = quoteTypeCode::Travel;
    public const TYPE_ID = QuoteTypeId::Travel;

    /**
     * @var array<string, string>
     */
    private array $stepHandlers = [
        PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY => 'executeIssuePolicyStep',
        PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC => 'executeGetPolicyDocStep',
        PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE => 'executeGetBrokerInvoiceStep',
        PolicyIssuanceEnum::DIC_TRAVEL_BOOK_POLICY => 'executeBookPolicyStep',
    ];

    public function __construct(
        private DicStepExecutor $stepExecutor,
        private DicValidationService $validationService,
        private DicResponseHandler $responseHandler,
    ) {}

    /**
     * @return list<string>
     */
    private function getAPISteps(): array
    {
        return [
            PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
            PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC,
            PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE,
            PolicyIssuanceEnum::DIC_TRAVEL_BOOK_POLICY,
        ];
    }

    private function updateProcessWithStep(PolicyIssuance $process, array $response): void
    {
        if (($response['status'] ?? false) && isset($response['completed_step'])) {
            $process->update(['completed_step' => $response['completed_step']]);
            $process->refresh();
            LoggerService::info('DIC Travel: completed_step updated', [
                'process_id' => $process->id,
                'completed_step' => $response['completed_step'],
            ]);
        }
    }

    /**
     * Next DIC API step to run from {@see PolicyIssuance::$completed_step} (same ordering as sync {@see executeSteps}).
     */
    public function resolveAsyncStepToRun(PolicyIssuance $process): ?string
    {
        return $this->getNextStep($process->completed_step);
    }

    public function updateProcessCompletedStepFromResponse(PolicyIssuance $process, array $stepResponse): void
    {
        $this->updateProcessWithStep($process, $stepResponse);
    }

    /**
     * @return array<string, mixed>
     */
    public function runSingleDicAsyncStep(TravelQuote $quote, PolicyIssuance $process, string $step, bool $applyQuoteFailure): array
    {
        $handler = $this->getStepHandler($step);
        if (! $handler || ! method_exists($this->stepExecutor, $handler)) {
            $message = self::MISSING_HANDLER_MESSAGE_PREFIX.$step;

            return $this->responseHandler->buildStepResponse($step, false, $message, $message);
        }

        return $this->stepExecutor->{$handler}($quote, $process, $applyQuoteFailure);
    }

    /**
     * @return array{status: bool, error?: string, message?: string}
     */
    public function validateBeforeDicAsyncRun(TravelQuote $quote): array
    {
        return $this->validationService->validateRequiredData($quote);
    }

    private function getStepHandler(string $step): ?string
    {
        return $this->stepHandlers[$step] ?? null;
    }

    public function isPolicyIssuanceAutomationEnabled(): bool
    {
        return (bool) app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_DIC_TRAVEL_POLICY_ISSUANCE);
    }

    public function isPolicyIssuanceAutomationRetryEnabledForTimeout(): bool
    {
        return (bool) app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_DIC_TRAVEL_POLICY_ISSUANCE);
    }

    public function createPolicyIssuanceSchedule($quote, $insurer): mixed
    {
        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::DIC_TRAVEL_POLICY_AUTOMATION);
        LoggerService::info('DIC Travel: policy issuance schedule', [
            'quote_code' => $quote->code,
            'automation_enabled' => $this->isPolicyIssuanceAutomationEnabled(),
        ]);

        if ($this->isPolicyIssuanceAutomationEnabled()) {
            (new PolicyIssuanceService)->schedulePolicyIssuance($quote, $insurer, QuoteTypes::TRAVEL->value, self::class);
        }

        return $quote->policyIssuance;
    }

    /**
     * @return array<string, mixed>
     */
    public function executeSteps($process): array
    {
        if (! $this->isPolicyIssuanceAutomationEnabled()) {
            return [
                'status' => false,
                'error' => 'DIC Travel automation is disabled',
                'message' => 'DIC Travel automation is disabled',
            ];
        }

        $response = ['status' => false, 'error' => null, 'message' => null];
        $quote = $process->model;

        if (! $quote instanceof TravelQuote) {
            return [
                'status' => false,
                'error' => 'Invalid quote model for DIC Travel automation',
                'message' => 'Invalid quote model for DIC Travel automation',
            ];
        }

        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::DIC_TRAVEL_POLICY_AUTOMATION);
        LoggerService::info('DIC Travel: executeSteps started', [
            'process_id' => $process->id,
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

                LoggerService::info('DIC Travel: step sequence', [
                    'process_id' => $process->id,
                    'next_step' => $nextStepToBeExecuted,
                ]);

                $executeStepSequence = $this->executeStepSequence($quote, $process, $nextStepToBeExecuted);
                $response['status'] = $executeStepSequence['status'];
                $response['message'] = $executeStepSequence['message'];
                $response['error'] = $executeStepSequence['error'];
            }
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
            $shouldLogExecutionCompleted = false;
            LoggerService::error('DIC Travel: executeSteps exception', [
                'process_id' => $process->id,
                'quote_code' => $quote->code,
            ], exception: $e);
        }

        if ($shouldLogExecutionCompleted) {
            LoggerService::info('DIC Travel: executeSteps completed', [
                'process_id' => $process->id,
                'final_status' => $response['status'],
            ]);
        }

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function executeStepSequence(TravelQuote $quote, PolicyIssuance $process, ?string $nextStepToBeExecuted): array
    {
        $currentStep = $nextStepToBeExecuted;
        $allSteps = $this->getAPISteps();
        $stepsExecuted = [];
        $failureResponse = null;

        while ($currentStep !== null && $failureResponse === null) {
            if (! in_array($currentStep, $allSteps, true)) {
                $error = 'Unknown step: '.$currentStep;
                $failureResponse = $this->responseHandler->buildStepResponse($currentStep, false, null, $error);

                break;
            }

            $handler = $this->getStepHandler($currentStep);
            if (! $handler || ! method_exists($this->stepExecutor, $handler)) {
                $error = self::MISSING_HANDLER_MESSAGE_PREFIX.$currentStep;
                $failureResponse = $this->responseHandler->buildStepResponse($currentStep, false, null, $error);

                break;
            }

            LoggerService::info('DIC Travel: executing step', [
                'step' => $currentStep,
                'process_id' => $process->id,
            ]);

            $stepResponse = $this->stepExecutor->{$handler}($quote, $process);
            $stepsExecuted[] = $currentStep;

            if (isset($stepResponse['status']) && ! $stepResponse['status']) {
                $failureResponse = $stepResponse;

                break;
            }

            $this->updateProcessWithStep($process, $stepResponse);
            $currentStep = $this->getNextStep($process->completed_step);
        }

        if ($failureResponse !== null) {
            return $failureResponse;
        }

        app(PolicyIssuanceService::class)->applyTravelDicAutomationResult($quote, true);

        return [
            'status' => true,
            'message' => 'All DIC Travel policy issuance steps completed successfully',
            'completed_step' => $process->completed_step,
            'error' => null,
        ];
    }

    public function getNextStep(?string $completedStep = null): ?string
    {
        $allSteps = $this->getAPISteps();

        if (! $completedStep) {
            return $allSteps[0];
        }

        $completedStepIndex = array_search($completedStep, $allSteps, true);
        if ($completedStepIndex === false || $completedStepIndex === count($allSteps) - 1) {
            return null;
        }

        return $allSteps[$completedStepIndex + 1];
    }

    public function getInsurerAPIStatusByStep($policyIssuance): ?int
    {
        $step = $this->getNextStep($policyIssuance->completed_step);

        return match ($step) {
            PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY => PolicyIssuanceEnum::POLICY_DETAIL_API_FAILED_STATUS_ID,
            PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC => PolicyIssuanceEnum::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID,
            PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE => PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID,
            PolicyIssuanceEnum::DIC_TRAVEL_BOOK_POLICY => PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID,
            default => null,
        };
    }

    /**
     * DIC does not use automation step-locking in the UI. Same keys as other travel automations so
     * PolicyIssuanceService, FormRequests, and Inertia props always receive a complete payload.
     *
     * @param  mixed  $throughAutomation
     * @return array<string, mixed>
     */
    public function getStepsLockingStatus($quote): array
    {
        return [
            'policyIssuance' => $quote->policyIssuance,
            'isEditPolicyDetailsDisabled' => false,
            'isPolicyDocumentUploadDisabled' => false,
            'isEditBookingDetailsDisabled' => false,
            'message' => '',
            'insurer_api_status' => $quote->insurer_api_status,
        ];
    }

    public function updateQuoteApiIssuanceStatusAndAllocate(TravelQuote $quote, $newInsurerApiStatus = null, $newApiIssuanceStatus = null): void
    {
        if (! $newInsurerApiStatus) {
            return;
        }

        $processInvolved = match ((int) $newInsurerApiStatus) {
            PolicyIssuanceEnum::POLICY_DETAIL_API_FAILED_STATUS_ID => PolicyIssuanceEnum::PROCESS_INVOLVED_ISSUE_POLICY,
            PolicyIssuanceEnum::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID => PolicyIssuanceEnum::PROCESS_INVOLVED_UPLOAD_DOCUMENTS,
            PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID => PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY,
            default => PolicyIssuanceEnum::PROCESS_INVOLVED_ISSUE_POLICY,
        };

        app(PolicyIssuanceService::class)->applyTravelDicAutomationResult(
            $quote,
            false,
            (int) $newInsurerApiStatus,
            $processInvolved,
            $newApiIssuanceStatus !== null ? (int) $newApiIssuanceStatus : null,
        );
    }

    /**
     * @param  array<string, mixed>  $stepResult
     * @return array{insurer_api_status_id: int, process_involved: string}
     */
    public function resolveTravelDicAsyncFailureContext(string $step, array $stepResult): array
    {
        if (isset($stepResult['travel_dic_insurer_api_status_id'], $stepResult['travel_dic_process_involved'])) {
            return [
                'insurer_api_status_id' => (int) $stepResult['travel_dic_insurer_api_status_id'],
                'process_involved' => (string) $stepResult['travel_dic_process_involved'],
            ];
        }

        return match ($step) {
            PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY => [
                'insurer_api_status_id' => PolicyIssuanceEnum::POLICY_DETAIL_API_FAILED_STATUS_ID,
                'process_involved' => PolicyIssuanceEnum::PROCESS_INVOLVED_ISSUE_POLICY,
            ],
            PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC => [
                'insurer_api_status_id' => PolicyIssuanceEnum::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID,
                'process_involved' => PolicyIssuanceEnum::PROCESS_INVOLVED_UPLOAD_DOCUMENTS,
            ],
            PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE => [
                'insurer_api_status_id' => PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID,
                'process_involved' => PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY,
            ],
            PolicyIssuanceEnum::DIC_TRAVEL_BOOK_POLICY => [
                'insurer_api_status_id' => PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID,
                'process_involved' => PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY,
            ],
            default => [
                'insurer_api_status_id' => PolicyIssuanceEnum::POLICY_DETAIL_API_FAILED_STATUS_ID,
                'process_involved' => PolicyIssuanceEnum::PROCESS_INVOLVED_ISSUE_POLICY,
            ],
        };
    }
}
