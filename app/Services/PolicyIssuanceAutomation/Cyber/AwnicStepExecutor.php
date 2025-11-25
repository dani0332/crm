<?php

namespace App\Services\PolicyIssuanceAutomation\Cyber;

use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;

class AwnicStepExecutor
{
    private string $className = 'AwnicStepExecutor';

    public const UPLOAD_DOCUMENTS = 'UploadDocuments';
    public const ISSUE_POLICY = 'IssuePolicy';
    public const UPLOAD_POLICY_DOCUMENTS_TO_IMCRM = 'UploadPolicyDocumentsToIMCRM';
    public const BOOK_POLICY = 'BookPolicy';

    public function __construct(
        private AwnicApiService $apiService,
        private AwnicBookPolicyService $bookPolicyService,
        private AwnicResponseHandler $responseHandler,
    ) {}

    /**
     * Execute upload documents step
     *
     * @param mixed $quote
     * @param mixed $process
     * @return array
     */
    public function executeUploadDocumentsStep($quote, $process): array
    {
        LoggerService::info('Starting document upload', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'step' => self::UPLOAD_DOCUMENTS,
            'process_id' => $process->id,
        ]);

        $uploadDocumentsResponse = $this->apiService->uploadDocuments($quote, $process);

        if (! $uploadDocumentsResponse['status']) {
            LoggerService::error('Document upload failed', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
                'step' => self::UPLOAD_DOCUMENTS,
                'error' => $uploadDocumentsResponse['error'] ?? 'Unknown error',
                'message' => $uploadDocumentsResponse['message'] ?? null,
            ]);

            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CYBER->value, PolicyIssuanceEnum::PIA_UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID, PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID, 'Document Upload');

            return $uploadDocumentsResponse;
        }

        LoggerService::info('Document upload successful', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'step' => self::UPLOAD_DOCUMENTS,
        ]);

        return $uploadDocumentsResponse;
    }

    /**
     * Execute issue policy step
     *
     * @param mixed $quote
     * @param mixed $process
     * @return array
     */
    public function executeIssuePolicyStep($quote, $process): array
    {
        LoggerService::info('Starting policy issuance', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'step' => self::ISSUE_POLICY,
            'process_id' => $process->id,
            'plan_id' => $quote->plan_id,
        ]);

        $policyIssuanceResponse = $this->apiService->issuePolicy($quote, $process);

        if (! $policyIssuanceResponse['status']) {
            LoggerService::error('Policy issuance failed', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
                'step' => self::ISSUE_POLICY,
                'error' => $policyIssuanceResponse['error'] ?? 'Unknown error',
                'message' => $policyIssuanceResponse['message'] ?? null,
            ]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CYBER->value, PolicyIssuanceEnum::PIA_POLICY_ISSUANCE_API_FAILED_STATUS_ID, PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID, 'Policy Creation');

            return $policyIssuanceResponse;
        }

        LoggerService::info('Policy issued successfully, updating quote status', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'step' => self::ISSUE_POLICY,
            'policy_number' => $policyIssuanceResponse['data']->policyInfo?->policyNo ?? null,
        ]);

        return $policyIssuanceResponse;
    }

    /**
     * Execute upload policy documents step
     *
     * @param mixed $quote
     * @param mixed $process
     * @return array
     */
    public function executeUploadPolicyDocumentsStep($quote, $process): array
    {
        LoggerService::info('Starting policy document upload to IMCRM', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'step' => self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
            'process_id' => $process->id,
        ]);

        $uploadPolicyDocumentsToIMCRMResponse = $this->apiService->uploadPolicyDocumentsToIMCRM($quote, $process);

        if (! $uploadPolicyDocumentsToIMCRMResponse['status']) {
            LoggerService::error('Policy document upload to IMCRM failed', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
                'step' => self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
                'error' => $uploadPolicyDocumentsToIMCRMResponse['error'] ?? 'Unknown error',
                'message' => $uploadPolicyDocumentsToIMCRMResponse['message'] ?? null,
            ]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CYBER->value, PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID, PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID, 'Retrieve Document');

            return $uploadPolicyDocumentsToIMCRMResponse;
        }

        LoggerService::info('Policy documents uploaded to IMCRM successfully', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'step' => self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
        ]);

        return $uploadPolicyDocumentsToIMCRMResponse;
    }

    /**
     * Execute book policy step
     *
     * @param mixed $quote
     * @param mixed $process
     * @return array
     */
    public function executeBookPolicyStep($quote, $process): array
    {
        LoggerService::info('Starting book policy execution', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'step' => self::BOOK_POLICY,
            'process_id' => $process->id,
            'policy_number' => $quote->policy_number,
        ]);

        $triggerBookPolicyResponse = $this->bookPolicyService->bookPolicy($quote, $process);

        if (! $triggerBookPolicyResponse['status']) {
            LoggerService::error('Book policy failed', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
                'step' => self::BOOK_POLICY,
                'error' => $triggerBookPolicyResponse['error'] ?? 'Unknown error',
                'message' => $triggerBookPolicyResponse['message'] ?? null,
            ]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CYBER->value, PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID, PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID, 'Send And Book Policy');

            return $triggerBookPolicyResponse;
        }

        LoggerService::info('Book policy execution successful', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'step' => self::BOOK_POLICY,
        ]);

        return $triggerBookPolicyResponse;
    }
}

