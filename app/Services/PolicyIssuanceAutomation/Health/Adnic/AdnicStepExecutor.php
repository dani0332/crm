<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Health\Adnic;

use App\Enums\AdnicEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;

class AdnicStepExecutor
{
    public $healthInsurerRequest = '';
    public $healthInsurerResponse = '';
    public function __construct(
        private AdnicApiService $apiService,
        private AdnicResponseHandler $responseHandler,
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
            'step' => AdnicEnum::STEP_ISSUE_POLICY,
            'process_id' => $process->id,
            'plan_id' => $quote->plan_id,
        ]);

        if (! $quote->insurerGenerateQuoteRequestResponse) {
            $message = 'Insurer generate-quote request/response not found for this quote.';

            return $this->responseHandler->buildStepResponse(
                AdnicEnum::STEP_ISSUE_POLICY,
                false,
                $message,
                $message,
            );
        }

        $policyIssuanceResponse = $this->apiService->issuePolicy($quote, $process, $quote->insurerGenerateQuoteRequestResponse);

        if (! $policyIssuanceResponse['status']) {
            LoggerService::warning('Policy issuance failed', extra: [
                'step' => AdnicEnum::STEP_ISSUE_POLICY,
                'error' => $policyIssuanceResponse['error'] ?? AdnicEnum::UNKNOWN_ERROR,
                'message' => $policyIssuanceResponse['message'] ?? null,
            ]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::HEALTH->value, PolicyIssuanceEnum::PIA_POLICY_ISSUANCE_API_FAILED_STATUS_ID, PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID, 'IssuePolicy');

            return $policyIssuanceResponse;
        }

        LoggerService::info('Policy issued successfully, updating quote status', extra: [
            'step' => AdnicEnum::STEP_ISSUE_POLICY,
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
            'step' => AdnicEnum::STEP_UPLOAD_DOCUMENTS,
            'process_id' => $process->id,
        ]);

        if (! $quote->insurerGenerateQuoteRequestResponse) {
            $message = 'Insurer generate-quote request/response not found for this quote.';

            return $this->responseHandler->buildStepResponse(
                AdnicEnum::STEP_UPLOAD_DOCUMENTS,
                false,
                $message,
                $message,
            );
        }

        $uploadDocumentsResponse = $this->apiService->uploadDocuments($quote, $process, $quote->insurerGenerateQuoteRequestResponse);

        if (! $uploadDocumentsResponse['status']) {
            LoggerService::warning('Document upload failed', extra: [
                'step' => AdnicEnum::STEP_UPLOAD_DOCUMENTS,
                'error' => $uploadDocumentsResponse['error'] ?? AdnicEnum::UNKNOWN_ERROR,
                'message' => $uploadDocumentsResponse['message'] ?? null,
            ]);

            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::HEALTH->value, PolicyIssuanceEnum::PIA_UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID, PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID, 'UploadDocuments');

            return $uploadDocumentsResponse;
        }

        LoggerService::info('Document upload successful', extra: [
            'step' => AdnicEnum::STEP_UPLOAD_DOCUMENTS,
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
            'step' => AdnicEnum::STEP_UPLOAD_POLICY_DOCS,
            'process_id' => $process->id,
        ]);

        $uploadPolicyDocumentsToIMCRMResponse = $this->apiService->uploadPolicyDocumentsToIMCRM($quote, $process);

        if (! $uploadPolicyDocumentsToIMCRMResponse['status']) {
            LoggerService::warning('Policy document upload to IMCRM failed', extra: [
                'step' => AdnicEnum::STEP_UPLOAD_POLICY_DOCS,
                'error' => $uploadPolicyDocumentsToIMCRMResponse['error'] ?? AdnicEnum::UNKNOWN_ERROR,
                'message' => $uploadPolicyDocumentsToIMCRMResponse['message'] ?? null,
            ]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::HEALTH->value, PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID, PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID, 'UploadPolicyDocumentsToIMCRM');

            return $uploadPolicyDocumentsToIMCRMResponse;
        }

        LoggerService::info('Policy documents uploaded to IMCRM successfully', extra: [
            'step' => AdnicEnum::STEP_UPLOAD_POLICY_DOCS,
        ]);

        return $uploadPolicyDocumentsToIMCRMResponse;
    }
}
