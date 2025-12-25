<?php

namespace App\Services\PolicyIssuanceAutomation\Cyber;

use App\Enums\AwnicEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;

class AwnicStepExecutor
{
    public function __construct(
        private AwnicApiService $apiService,
        private AwnicBookPolicyService $bookPolicyService,
    ) {}

    /**
     * Execute issue policy step
     *
     * @param  mixed  $quote
     * @param  mixed  $process
     */
    public function executeIssuePolicyStep($quote, $process): array
    {
        LoggerService::info('Starting policy issuance', extra: [
            'step' => AwnicEnum::STEP_ISSUE_POLICY,
            'process_id' => $process->id,
            'plan_id' => $quote->plan_id,
        ]);

        $policyIssuanceResponse = $this->apiService->issuePolicy($quote, $process);

        if (! $policyIssuanceResponse['status']) {
            LoggerService::error('Policy issuance failed', extra: [
                'step' => AwnicEnum::STEP_ISSUE_POLICY,
                'error' => $policyIssuanceResponse['error'] ?? AwnicEnum::UNKNOWN_ERROR,
                'message' => $policyIssuanceResponse['message'] ?? null,
            ]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CYBER->value, PolicyIssuanceEnum::PIA_POLICY_ISSUANCE_API_FAILED_STATUS_ID, PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID, PolicyIssuanceEnum::PROCESS_INVOLVED_ISSUE_POLICY);

            return $policyIssuanceResponse;
        }

        LoggerService::info('Policy issued successfully, updating quote status', extra: [
            'step' => AwnicEnum::STEP_ISSUE_POLICY,
            'policy_number' => $policyIssuanceResponse['data']->policyInfo?->policyNo ?? null,
        ]);

        return $policyIssuanceResponse;
    }

    /**
     * Execute upload documents step
     *
     * @param  mixed  $quote
     * @param  mixed  $process
     */
    public function executeUploadDocumentsStep($quote, $process): array
    {
        LoggerService::info('Starting document upload', extra: [
            'step' => AwnicEnum::STEP_UPLOAD_DOCUMENTS,
            'process_id' => $process->id,
        ]);

        $uploadDocumentsResponse = $this->apiService->uploadDocuments($quote, $process);

        if (! $uploadDocumentsResponse['status']) {
            LoggerService::error('Document upload failed', extra: [
                'step' => AwnicEnum::STEP_UPLOAD_DOCUMENTS,
                'error' => $uploadDocumentsResponse['error'] ?? AwnicEnum::UNKNOWN_ERROR,
                'message' => $uploadDocumentsResponse['message'] ?? null,
            ]);

            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CYBER->value, PolicyIssuanceEnum::PIA_UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID, PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID, PolicyIssuanceEnum::PROCESS_INVOLVED_UPLOAD_DOCUMENTS);

            return $uploadDocumentsResponse;
        }

        LoggerService::info('Document upload successful', extra: [
            'step' => AwnicEnum::STEP_UPLOAD_DOCUMENTS,
        ]);

        return $uploadDocumentsResponse;
    }

    /**
     * Execute upload policy documents step
     *
     * @param  mixed  $quote
     * @param  mixed  $process
     */
    public function executeUploadPolicyDocumentsStep($quote, $process): array
    {
        LoggerService::info('Starting policy document upload to IMCRM', extra: [
            'step' => AwnicEnum::STEP_UPLOAD_POLICY_DOCS,
            'process_id' => $process->id,
        ]);

        $uploadPolicyDocumentsToIMCRMResponse = $this->apiService->uploadPolicyDocumentsToIMCRM($quote, $process);

        if (! $uploadPolicyDocumentsToIMCRMResponse['status']) {
            LoggerService::error('Policy document upload to IMCRM failed', extra: [
                'step' => AwnicEnum::STEP_UPLOAD_POLICY_DOCS,
                'error' => $uploadPolicyDocumentsToIMCRMResponse['error'] ?? AwnicEnum::UNKNOWN_ERROR,
                'message' => $uploadPolicyDocumentsToIMCRMResponse['message'] ?? null,
            ]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CYBER->value, PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID, PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID, PolicyIssuanceEnum::PROCESS_INVOLVED_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM);

            return $uploadPolicyDocumentsToIMCRMResponse;
        }

        LoggerService::info('Policy documents uploaded to IMCRM successfully', extra: [
            'step' => AwnicEnum::STEP_UPLOAD_POLICY_DOCS,
        ]);

        return $uploadPolicyDocumentsToIMCRMResponse;
    }

    /**
     * Execute book policy step
     *
     * @param  mixed  $quote
     * @param  mixed  $process
     */
    public function executeBookPolicyStep($quote, $process): array
    {
        LoggerService::info('Starting book policy execution', extra: [
            'step' => AwnicEnum::STEP_BOOK_POLICY,
            'process_id' => $process->id,
            'policy_number' => $quote->policy_number,
        ]);

        $triggerBookPolicyResponse = $this->bookPolicyService->bookPolicy($quote, $process);

        // this email is used to test the book policy automation failure scenario
        if (! $triggerBookPolicyResponse['status'] || $quote->email == PolicyIssuanceEnum::FAKE_EMAIL_IMCRM_BOOK_POLICY) {
            LoggerService::error('Book policy failed', extra: [
                'step' => AwnicEnum::STEP_BOOK_POLICY,
                'error' => $triggerBookPolicyResponse['error'] ?? AwnicEnum::UNKNOWN_ERROR,
                'message' => $triggerBookPolicyResponse['message'] ?? null,
            ]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CYBER->value, PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID, PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID, PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY);

            return $triggerBookPolicyResponse;
        }

        LoggerService::info('Book policy execution successful', extra: [
            'step' => AwnicEnum::STEP_BOOK_POLICY,
        ]);

        return $triggerBookPolicyResponse;
    }
}
