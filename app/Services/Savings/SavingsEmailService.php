<?php

declare(strict_types=1);

namespace App\Services\Savings;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\PersonalQuote;
use App\Models\QuoteFlowDetails;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;

class SavingsEmailService
{
    /**
     * Send OCA (One Click Apply) Email for Savings quote
     *
     * @param  string  $quoteUID  The quote UUID
     * @param  array  $data  Additional data (e.g., plan_ids)
     * @return mixed Response from Bird service or false on failure
     */
    public function sendOCAEmail(string $quoteUID, array $data = []): mixed
    {
        LoggerService::startQuoteLogging($quoteUID);

        $logPrefix = self::class.' fn: sendOCAEmail - ';

        LoggerService::info($logPrefix.' - Sending OCA email');

        $lead = $this->getQuote($quoteUID);

        if (! $lead) {
            LoggerService::warning($logPrefix.' - Quote not found');

            return false;
        }

        if ($lead->isSuppressIntroEmail()) {
            LoggerService::info('sendOCAEmail - Suppressing OCA Email');

            return null;
        }

        // Check if OCA email flow already executed to prevent duplicates (bypass when force_send from manual button)
        $forceSend = $data['force_send'] ?? false;
        if (! $forceSend) {
            $isFlowExecuted = app(BirdService::class)->isFollowupExecuted(
                $lead->uuid,
                QuoteTypeId::Savings,
                QuoteFlowType::SAVINGS_OCA_EMAIL->value
            );

            if ($isFlowExecuted) {
                LoggerService::info("$logPrefix OCA email flow already executed for quote: {$lead->uuid}");

                return null;
            }
        }

        // Map data for bird service
        $emailData = $this->mapOCAEmailData($lead, $data);

        // Get bird flow url for Savings from ApplicationStorage
        $flowUrl = $this->getApplicationStorage();
        if (! $flowUrl) {
            LoggerService::info($logPrefix.' - Flow URL not found');

            return false;
        }

        try {
            $response = app(BirdService::class)->triggerWebHookRequest($flowUrl, $emailData);

            if ($response && $response->status_code == 200) {
                // Create quote flow details to track the email flow (header casing varies by server)
                $runId = $response->headers['Run-Id'] ?? $response->headers['run-id'] ?? null;
                $runId = is_array($runId) ? collect($runId)->first() : $runId;
                if (! empty($runId)) {
                    $this->createQuoteFlowDetails($lead, $response);
                }

                $updates = [];
                if (! $lead->advisor_id || ! $lead->advisor) {
                    $updates['non_advisor_email_sent_at'] = now();
                }
                if ($lead->quote_status_id == QuoteStatusEnum::NewLead) {
                    $updates['quote_status_id'] = QuoteStatusEnum::Quoted;
                }
                if (! empty($updates)) {
                    PersonalQuote::where('uuid', $lead->uuid)
                        ->where('quote_type_id', QuoteTypeId::Savings)
                        ->update($updates);
                }

                LoggerService::info("$logPrefix Bird flow triggered successfully - Email sent to customer", extra: [
                    'email' => $emailData->customerEmail,
                ]);
            }

            return $response ?? null;
        } catch (\Exception $e) {
            LoggerService::error("$logPrefix Error triggering event | Message: {$e->getMessage()} Line: {$e->getLine()}");

            return false;
        }
    }

    /**
     * Get the Bird flow URL from Application Storage
     */
    private function getApplicationStorage(): ?string
    {
        return ApplicationStorage::where('key_name', ApplicationStorageEnums::SAVINGS_OCA_EMAIL_FLOW)->value('value');
    }

    /**
     * Map lead data for OCA email
     */
    private function mapOCAEmailData(PersonalQuote $lead, array $data): object
    {
        $firstName = $lead->first_name;
        $lastName = $lead->last_name;
        $customerFullName = trim("{$firstName} {$lastName}");
        $advisor = $lead->advisor;
        $workflowType = WorkflowTypeEnum::SAVINGS_OCA_EMAIL;
        $planIds = isset($data['plan_ids']) && is_array($data['plan_ids'])
            ? implode(',', $data['plan_ids'])
            : ($data['plan_ids'] ?? '');

        return (object) [
            // Lead-related data
            'quoteUID' => $lead->uuid,
            'uuid' => $lead->uuid,
            'customerEmail' => $lead->email,
            'customerFullName' => $customerFullName,
            'customerName' => $customerFullName,
            'refID' => $lead->code,
            'customerMobile' => $lead->mobile_no ?? null,
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::SAVINGS, $lead->uuid),
            'flowExecutedAt' => $lead->automated_flow_executed_at ?? null,

            // Advisor-related data
            'advisorId' => $advisor?->id,
            'advisorName' => $advisor?->name ?? null,
            'advisorEmail' => $advisor?->email ?? null,
            'advisorDetails' => $advisor ?? null,
            'landLine' => $advisor?->landline_no ?? null,
            'mobilePhone' => $advisor?->mobile_no ?? null,
            'whatsAppNumber' => $advisor?->mobile_no ? formatMobileNo($advisor->mobile_no) : null,
            'mobileNoWithoutSpaces' => $advisor?->mobile_no ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : null,
            'planIds' => $planIds,

            // Workflow-related data
            'workflowType' => $workflowType,
        ];
    }

    /**
     * Get the quote by UUID
     */
    private function getQuote(string $quoteUID): ?PersonalQuote
    {
        return PersonalQuote::where([
            'uuid' => $quoteUID,
            'quote_type_id' => QuoteTypeId::Savings,
        ])->with('advisor')->first();
    }

    /**
     * Create quote flow details for tracking email campaigns
     */
    private function createQuoteFlowDetails(PersonalQuote $lead, $response): void
    {
        try {
            $runId = '';
            if (isset($response->headers['Run-Id'])) {
                $runId = is_array($response->headers['Run-Id'])
                    ? collect($response->headers['Run-Id'])->first()
                    : $response->headers['Run-Id'];
            } elseif (isset($response->headers['run-id'])) {
                $runId = is_array($response->headers['run-id'])
                    ? collect($response->headers['run-id'])->first()
                    : $response->headers['run-id'];
            }

            if (! empty($runId)) {
                QuoteFlowDetails::create([
                    'quote_uuid' => $lead->uuid,
                    'quote_type_id' => QuoteTypeId::Savings,
                    'flow_type' => QuoteFlowType::SAVINGS_OCA_EMAIL->value,
                    'flow_id' => $runId,
                    'started_at' => now(),
                ]);
                LoggerService::info("Savings OCA email flow details created for quote: {$lead->uuid} | Run-Id: {$runId}");
            } else {
                LoggerService::warning("Savings OCA email Run-Id not found in response headers for quote: {$lead->uuid}");
            }
        } catch (\Throwable $th) {
            LoggerService::error("Error creating quote flow details for Savings OCA email - Quote: {$lead->uuid}", [
                'error' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ], $th);
        }
    }
}
