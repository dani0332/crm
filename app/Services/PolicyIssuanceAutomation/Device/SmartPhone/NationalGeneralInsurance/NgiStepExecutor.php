<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance;

use App\Enums\NgiEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;

class NgiStepExecutor
{
    public function __construct(
        private NgiApiService $apiService,
        private NgiBookPolicyService $bookPolicyService,
    ) {}

    /**
     * Execute create policy from quote step
     *
     * @param mixed $quote
     * @param mixed $process
     * @return array
     */
    public function executeCreatePolicyFromQuoteStep($quote, $process, $customer = null, $deviceQuote = null, $latestInsured = null): array
    {
        LoggerService::info('Starting policy creation from quote', extra: [
            'step' => NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
            'process_id' => $process->id,
            'insurer_quote_number' => $quote->insurer_quote_number,
        ]);

        $createPolicyResponse = $this->apiService->createPolicyFromQuote($quote, $process, $customer, $deviceQuote, $latestInsured);

        if (! $createPolicyResponse['status']) {
            LoggerService::error('Policy creation failed', extra: [
                'step' => NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
                'error' => $createPolicyResponse['error'] ?? NgiEnum::UNKNOWN_ERROR,
                'message' => $createPolicyResponse['message'] ?? null,
            ]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus(
                $quote,
                QuoteTypes::DEVICE->value,
                PolicyIssuanceEnum::PIA_POLICY_ISSUANCE_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID,
                'Policy Creation'
            );

            return $createPolicyResponse;
        }

        LoggerService::info('Policy created successfully', extra: [
            'step' => NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
            'policy_number' => $createPolicyResponse['data']?->policy_no ?? null,
        ]);

        return $createPolicyResponse;
    }

    /**
     * Execute get policy documents step
     *
     * @param mixed $quote
     * @param mixed $process
     * @return array
     */
    public function executeGetPolicyDocumentsStep($quote, $process, $customer = null, $deviceQuote = null, $latestInsured = null): array
    {

        // need to check 3 minutes difference

        LoggerService::info('Starting get policy documents', extra: [
            'step' => NgiEnum::STEP_GET_POLICY_DOCUMENTS,
            'process_id' => $process->id,
            'policy_number' => $quote->policy_number,
        ]);

        $getPolicyDocumentsResponse = $this->apiService->getPolicyDocuments($quote, $process, $customer, $deviceQuote, $latestInsured);

        if (! $getPolicyDocumentsResponse['status']) {
            LoggerService::error('Get policy documents failed', extra: [
                'step' => NgiEnum::STEP_GET_POLICY_DOCUMENTS,
                'error' => $getPolicyDocumentsResponse['error'] ?? NgiEnum::UNKNOWN_ERROR,
                'message' => $getPolicyDocumentsResponse['message'] ?? null,
            ]);

            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus(
                $quote,
                QuoteTypes::DEVICE->value,
                PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID,
                'Get Policy Documents'
            );

            return $getPolicyDocumentsResponse;
        }

        LoggerService::info('Get policy documents successful', extra: [
            'step' => NgiEnum::STEP_GET_POLICY_DOCUMENTS,
        ]);

        return $getPolicyDocumentsResponse;
    }

    /**
     * Execute upload policy documents step
     *
     * @param mixed $quote
     * @param mixed $process
     * @return array
     */
    public function executeUploadPolicyDocumentsStep($quote, $process, $customer = null, $deviceQuote = null, $latestInsured = null): array
    {
        LoggerService::info('Starting policy document upload to IMCRM', extra: [
            'step' => NgiEnum::STEP_UPLOAD_POLICY_DOCS,
            'process_id' => $process->id,
        ]);

        $uploadPolicyDocumentsToIMCRMResponse = $this->apiService->uploadPolicyDocumentsToIMCRM($quote, $process, $customer, $deviceQuote, $latestInsured);

        if (! $uploadPolicyDocumentsToIMCRMResponse['status']) {
            LoggerService::error('Policy document upload to IMCRM failed', extra: [
                'step' => NgiEnum::STEP_UPLOAD_POLICY_DOCS,
                'error' => $uploadPolicyDocumentsToIMCRMResponse['error'] ?? NgiEnum::UNKNOWN_ERROR,
                'message' => $uploadPolicyDocumentsToIMCRMResponse['message'] ?? null,
            ]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus(
                $quote,
                QuoteTypes::DEVICE->value,
                PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID,
                'Retrieve Document'
            );

            return $uploadPolicyDocumentsToIMCRMResponse;
        }

        LoggerService::info('Policy documents uploaded to IMCRM successfully', extra: [
            'step' => NgiEnum::STEP_UPLOAD_POLICY_DOCS,
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
    public function executeBookPolicyStep($quote, $process, $customer = null, $deviceQuote = null, $latestInsured = null): array
    {
        LoggerService::info('Starting book policy execution', extra: [
            'step' => NgiEnum::STEP_BOOK_POLICY,
            'process_id' => $process->id,
            'policy_number' => $quote->policy_number,
        ]);

        $triggerBookPolicyResponse = $this->bookPolicyService->bookPolicy($quote, $process, $customer, $deviceQuote, $latestInsured);

        if (! $triggerBookPolicyResponse['status']) {
            LoggerService::error('Book policy failed', extra: [
                'step' => NgiEnum::STEP_BOOK_POLICY,
                'error' => $triggerBookPolicyResponse['error'] ?? NgiEnum::UNKNOWN_ERROR,
                'message' => $triggerBookPolicyResponse['message'] ?? null,
            ]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus(
                $quote,
                QuoteTypes::DEVICE->value,
                PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID, // TODO::: NGI:: confirm it
                PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID, // TODO::: NGI:: confirm it
                'Send And Book Policy'
            );

            return $triggerBookPolicyResponse;
        }

        LoggerService::info('Book policy execution successful', extra: [
            'step' => NgiEnum::STEP_BOOK_POLICY,
        ]);

        return $triggerBookPolicyResponse;
    }
}
