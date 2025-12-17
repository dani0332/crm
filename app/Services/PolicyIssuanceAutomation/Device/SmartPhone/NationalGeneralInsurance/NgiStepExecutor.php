<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance;

use App\Enums\DeviceFailureTypeEnum;
use App\Enums\NgiEnum;
use App\Enums\QuoteTypes;
use App\Enums\PolicyIssuanceEnum;
use App\Services\DeviceFailureEmailService;
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

            // Trigger failure email for Policy Issuance API failure
            app(DeviceFailureEmailService::class)->sendFailureEmail(
                $quote->id,
                DeviceFailureTypeEnum::ISSUE_POLICY
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
     * Execute get policy documents and upload to IMCRM step - dispatches async job with FRD-compliant delay
     *
     * FRD Requirements:
     * - Wait 3 minutes after policy creation before calling GetPolicyDocuments
     * - Retry up to 3 times with 5-minute gaps (handled by job's $tries and $backoff)
     * - Download documents from provider and store to IMCRM
     *
     * @param mixed $quote
     * @param mixed $process
     * @return array
     */
    public function executeGetPolicyDocumentsAndUploadToIMCRMStep($quote, $process, $customer = null, $deviceQuote = null, $latestInsured = null): array
    {
        LoggerService::info('Dispatching ' . NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM . ' job with 3-minute delay per FRD', extra: [
            'step' => NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
            'process_id' => $process->id,
            'policy_number' => $quote->policy_number,
        ]);

        // Dispatch job with 3-minute delay per FRD requirement
        $delayMinutes = NgiEnum::DOCUMENT_FETCH_DELAY_MINUTES;

        NgiGetPolicyDocumentsJob::dispatch($process->id)
            ->delay(now()->addMinutes($delayMinutes));

        $scheduledAt = now()->addMinutes($delayMinutes)->toDateTimeString();

        LoggerService::info(NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM . ' job dispatched successfully', extra: [
            'step' => NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
            'process_id' => $process->id,
            'delay_minutes' => $delayMinutes,
            'scheduled_at' => $scheduledAt,
        ]);

        return [
            'status' => true,
            'documents_pending' => true,
            'message' => NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM . " job dispatched with {$delayMinutes}-minute delay. Scheduled at: {$scheduledAt}",
            'completed_step' => NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
        ];
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

        $triggerBookPolicyResponse = $this->bookPolicyService->bookPolicy($quote, $process);

        if (! $triggerBookPolicyResponse['status']) {
            LoggerService::error('Book policy failed', extra: [
                'step' => NgiEnum::STEP_BOOK_POLICY,
                'error' => $triggerBookPolicyResponse['error'] ?? NgiEnum::UNKNOWN_ERROR,
                'message' => $triggerBookPolicyResponse['message'] ?? null,
            ]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus(
                $quote,
                QuoteTypes::DEVICE->value,
                PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID,
                PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID,
                'Send And Book Policy'
            );

            // Trigger failure email for Booking Details API failure
            app(DeviceFailureEmailService::class)->sendFailureEmail(
                $quote->id,
                DeviceFailureTypeEnum::BOOK_POLICY
            );

            return $triggerBookPolicyResponse;
        }

        LoggerService::info('Book policy execution successful', extra: [
            'step' => NgiEnum::STEP_BOOK_POLICY,
        ]);

        return $triggerBookPolicyResponse;
    }
}
