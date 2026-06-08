<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance;

use App\Enums\NgiEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Models\PolicyIssuance;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\PolicyIssuanceFailureEmailService;

/**
 * Service to handle policy document retrieval from NGI provider.
 *
 * This service encapsulates all the business logic for:
 * - Validating policy issuance process and quote
 * - Calling the GetPolicyDocuments API
 * - Downloading and storing documents
 * - Updating process status
 */
class NgiGetPolicyDocumentsService
{
    private string $logPrefix = 'NgiGetPolicyDocumentsService:';

    public function __construct(
        private readonly NgiValidationService $validationService,
        private readonly NgiApiService $apiService,
        private readonly NgiDocumentHandler $documentHandler,
        private readonly PolicyIssuanceService $policyIssuanceService,
        private readonly PolicyIssuanceFailureEmailService $failureEmailService,
    ) {}

    /**
     * Execute the policy document retrieval process.
     *
     * @param  int  $processId  The policy issuance process ID
     * @param  int  $attempt  Current attempt number (for logging)
     * @param  int  $maxTries  Maximum number of tries (for logging)
     * @return array{status: bool, error?: string, documents_count?: int}
     *
     * @throws NgiGetPolicyDocumentsException When validation or API calls fail
     */
    public function execute(int $processId, int $attempt = 1, int $maxTries = 4): array
    {
        $process = $this->getProcessWithQuote($processId);
        $quote = $process->model;

        LoggerService::info("{$this->logPrefix} Starting document retrieval", [
            'process_id' => $processId,
            'quote_code' => $quote->code,
            'policy_number' => $quote->policy_number,
            'attempt' => $attempt,
            'max_tries' => $maxTries,
        ]);

        // Step 1: Validate policy number exists
        $this->validatePolicyNumber($quote, $processId);

        // Step 2: Call GetPolicyDocuments API
        $providerDocumentsApiResponse = $this->apiService->getPolicyDocuments($quote, $process);

        // Step 3: Download documents from provider URLs and store in DB
        $downloadResult = $this->downloadDocuments($quote, $process, $processId, $providerDocumentsApiResponse);

        $isDocumentDownloadFailureEmail = $this->failureEmailService->isDocumentDownloadFailureEmail($quote->email);
        $isDocumentUploadFailureEmail = $this->failureEmailService->isDocumentUploadFailureEmail($quote->email);

        if (! $downloadResult['status']
            || $isDocumentDownloadFailureEmail
            || $isDocumentUploadFailureEmail
        ) {
            $errorMessage = '';
            $defaultErrorMsg = 'Document download from provider or upload to IMCRM failed';
            $errorMessage = $downloadResult['error'] ?? $defaultErrorMsg;

            if ($isDocumentDownloadFailureEmail || $isDocumentUploadFailureEmail) {
                $errorMessage .= ' '.sprintf(NgiEnum::FAILURE_EMAIL_DEFAULT_PREFIX_MESSAGE, $quote->email, 'document download from provider or upload to IMCRM');
            }

            throw new NgiGetPolicyDocumentsException(
                "{$this->logPrefix} {$errorMessage}",
                NgiGetPolicyDocumentsException::DOCUMENT_DOWNLOAD_FAILED,
                ['process_id' => $processId, 'error' => $errorMessage]
            );
        }

        // Step 4: Update process status for next step
        $this->updateProcessForNextStep($process, $processId, $downloadResult);

        return [
            'status' => true,
            'documents_count' => $downloadResult['documents_count'] ?? 0,
        ];
    }

    /**
     * Get policy issuance process with quote relationship.
     *
     * @throws NgiGetPolicyDocumentsException When process or quote not found
     */
    private function getProcessWithQuote(int $processId): PolicyIssuance
    {
        $process = PolicyIssuance::with('model')->find($processId);

        if (! $process) {
            LoggerService::error("{$this->logPrefix} Process not found", [
                'process_id' => $processId,
            ]);
            throw new NgiGetPolicyDocumentsException(
                "{$this->logPrefix} Process not found. process_id -> {$processId}",
                NgiGetPolicyDocumentsException::PROCESS_NOT_FOUND,
                ['process_id' => $processId]
            );
        }

        if (! $process->model) {
            LoggerService::error("{$this->logPrefix} Quote not found", [
                'process_id' => $processId,
            ]);
            throw new NgiGetPolicyDocumentsException(
                "{$this->logPrefix} Quote not found. process_id -> {$processId}",
                NgiGetPolicyDocumentsException::QUOTE_NOT_FOUND,
                ['process_id' => $processId]
            );
        }

        return $process;
    }

    /**
     * Validate that policy number exists on the quote.
     *
     * @throws NgiGetPolicyDocumentsException When policy number validation fails
     */
    private function validatePolicyNumber(PersonalQuote $quote, int $processId): void
    {
        $validationResult = $this->validationService->validatePolicyNumberExists($quote);

        if (! $validationResult['status']) {
            LoggerService::error("{$this->logPrefix} Policy number validation failed", [
                'process_id' => $processId,
                'error' => $validationResult['error'] ?? 'No policy number',
            ]);
            throw new NgiGetPolicyDocumentsException(
                "{$this->logPrefix} Policy number validation failed ".($validationResult['error'] ?? 'Policy number not found'),
                NgiGetPolicyDocumentsException::POLICY_NUMBER_VALIDATION_FAILED,
                ['process_id' => $processId, 'error' => $validationResult['error'] ?? 'Policy number not found']
            );
        }
    }

    /**
     * Download documents from provider URLs and store in DB.
     *
     * @return array{status: bool, documents_count?: int, error?: string}
     *
     * @throws NgiGetPolicyDocumentsException When document download fails
     */
    private function downloadDocuments(PersonalQuote $quote, PolicyIssuance $process, int $processId, array $documentsApiResponse): array
    {

        if (! $documentsApiResponse['status']) {
            return $documentsApiResponse;
        }

        $downloadResult = $this->documentHandler->downloadAndStorePolicyDocuments($quote, $process, $documentsApiResponse);

        if (! $downloadResult['status']) {
            $errorMessage = $downloadResult['error'] ?? 'Document download failed';
            LoggerService::error("{$this->logPrefix} Document download failed", [
                'process_id' => $processId,
                'quote_code' => $quote->code,
                'error' => $errorMessage,
            ]);
            $downloadResult['error'] = $errorMessage;

            return $downloadResult;
        }

        LoggerService::info("{$this->logPrefix} All documents downloaded successfully", [
            'process_id' => $processId,
            'quote_code' => $quote->code,
            'documents_count' => $downloadResult['documents_count'] ?? 0,
        ]);

        return $downloadResult;
    }

    /**
     * Update process status for the next step.
     */
    private function updateProcessForNextStep(PolicyIssuance $process, int $processId, array $downloadResult): void
    {
        $process->update([
            'completed_step' => NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
            'status' => PolicyIssuanceEnum::PENDING_STATUS,
            'message' => json_encode([
                'message' => 'Documents retrieved successfully, ready for upload step',
                'documents_downloaded' => $downloadResult['documents_count'] ?? 0,
            ]),
        ]);

        LoggerService::info("{$this->logPrefix} Process updated, ready for next steps", [
            'process_id' => $processId,
            'completed_step' => NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
            'new_status' => PolicyIssuanceEnum::PENDING_STATUS,
        ]);
    }

    /**
     * Handle job failure after all retries exhausted.
     */
    public function handleFailure(int $processId, int $totalAttempts, int $maxTries, string $exceptionMessage): void
    {
        $process = PolicyIssuance::with('model')->find($processId);

        LoggerService::error("{$this->logPrefix} Job failed permanently", [
            'process_id' => $processId,
            'quote_code' => $process?->model?->code ?? 'unknown',
            'total_attempts' => $totalAttempts,
            'max_tries' => $maxTries,
            'exception' => $exceptionMessage,
        ]);

        if (! $process) {
            return;
        }

        // Mark process as failed
        $process->update([
            'status' => PolicyIssuanceEnum::FAILED_STATUS,
            'message' => json_encode([
                'error' => NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM." failed after {$maxTries} attempts: {$exceptionMessage}",
                'step' => NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
                'final_attempt' => $totalAttempts,
            ]),
        ]);

        // Update quote API issuance status and send failure email
        if ($process->model) {
            $this->policyIssuanceService->updateAPIIssuanceAndInsurerStatus(
                $process->model,
                QuoteTypes::DEVICE->value,
                PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID,
                NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM
            );
        }
    }
}
