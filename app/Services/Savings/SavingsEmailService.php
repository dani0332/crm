<?php

declare(strict_types=1);

namespace App\Services\Savings;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\PersonalQuote;
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
                if ($lead->quote_status_id == QuoteStatusEnum::NewLead) {

                    $lead->quote_status_id = QuoteStatusEnum::Quoted;
                    PersonalQuote::where('uuid', $lead->uuid)
                        ->where('quote_type_id', QuoteTypeId::Savings)
                        ->update(['quote_status_id' => QuoteStatusEnum::Quoted]);
                    $lead->save();
                } else {
                    LoggerService::info("sendOCAEmail - Quote status is not new lead for quote: {$lead->uuid}");
                }
            }

            LoggerService::info("$logPrefix Bird flow triggered successfully - Email sent to customer", extra: [
                'email' => $emailData->customerEmail,
            ]);

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
}
