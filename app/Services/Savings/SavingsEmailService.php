<?php

declare(strict_types=1);

namespace App\Services\Savings;

use App\Enums\QuoteFlowType;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Models\PersonalQuote;
use App\Services\EmailServices\WebEngageService;
use App\Services\Logger\LoggerService;

class SavingsEmailService
{
    /**
     * Send OCA (One Click Apply) Email for Savings quote
     *
     * @param  string  $quoteUID  The quote UUID
     * @param  array  $data  Additional data (e.g., plan_ids)
     * @return mixed Response from WebEngage service or false on failure
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
            $isFlowExecuted = app(WebEngageService::class)->isFollowupExecuted(
                $lead->uuid,
                QuoteTypeId::Savings,
                QuoteFlowType::SAVINGS_OCA_EMAIL->value
            );

            if ($isFlowExecuted) {
                LoggerService::info("$logPrefix OCA email flow already executed for quote: {$lead->uuid}");

                return null;
            }
        }

        $emailData = $this->mapOCAEmailData($lead, $data);

        try {
            $response = app(WebEngageService::class)->sendEvent(WorkflowTypeEnum::SAVINGS_OCA_EMAIL, $emailData);

            app(WebEngageService::class)->createQuoteWorkFlowDetails(
                $lead->uuid,
                QuoteFlowType::SAVINGS_OCA_EMAIL->value,
                QuoteTypeId::Savings
            );

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

            LoggerService::info("$logPrefix WebEngage event triggered successfully - Email sent to customer", extra: [
                'email' => $emailData['customerEmail'],
            ]);

            return $response ?? null;
        } catch (\Exception $e) {
            LoggerService::error("$logPrefix Error triggering event | Message: {$e->getMessage()} Line: {$e->getLine()}");

            return false;
        }
    }

    /**
     * Map lead data for OCA email
     *
     * @return array<string, mixed>
     */
    private function mapOCAEmailData(PersonalQuote $lead, array $data): array
    {
        $firstName = $lead->first_name;
        $lastName = $lead->last_name;
        $customerFullName = trim("{$firstName} {$lastName}");
        $advisor = $lead->advisor;
        $planIds = isset($data['plan_ids']) && is_array($data['plan_ids'])
            ? implode(',', $data['plan_ids'])
            : ($data['plan_ids'] ?? '');

        return [
            'customerId' => $lead->customer_id ?? '',
            'firstName' => $firstName,
            'lastName' => $lastName,
            'quoteUID' => $lead->uuid,
            'customerEmail' => $lead->email,
            'customerFullName' => $customerFullName,
            'customerName' => $customerFullName,
            'refID' => $lead->code,
            'customerMobile' => ! empty($lead->mobile_no) ? '+'.formatMobileNoWithoutPlus($lead->mobile_no) : '',
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::SAVINGS, $lead->uuid),
            'flowExecutedAt' => $lead->automated_flow_executed_at ?? null,
            'advisorId' => $advisor?->id,
            'advisorName' => $advisor?->name ?? '',
            'advisorEmail' => $advisor?->email ?? '',
            'advisorLandLine' => $advisor?->landline_no ?? '',
            'advisorMobilePhone' => $advisor?->mobile_no ?? '',
            'advisorWhatsAppNumber' => $advisor?->mobile_no ? formatMobileNo($advisor->mobile_no) : '',
            'advisorMobileNoWithoutSpaces' => $advisor?->mobile_no ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : '',
            'planIds' => $planIds,
            'workflowType' => WorkflowTypeEnum::SAVINGS_OCA_EMAIL,
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
