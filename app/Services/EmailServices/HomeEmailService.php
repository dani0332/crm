<?php

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\HomeQuote;
use App\Models\User;
use App\Services\BaseService;
use App\Services\BirdService;

class HomeEmailService extends BaseService
{
    public function sendHomeOCBIntroEmail($lead)
    {
        if (! $lead) {
            info('sendHomeOCBIntroEmail - Lead not found | Time: ' . now());
            return false;
        }

        info("sendHomeOCBIntroEmail - Initiating process for Lead Ref ID: {$lead->uuid} | Time: " . now());

        // Fetch the advisor
        $advisor = User::find($lead->advisor_id);
        if (! $advisor) {
            info("sendHomeOCBIntroEmail - Advisor not found for Lead Ref ID: {$lead->uuid} | Time: " . now());
        }

        // Fetch home quote
        $homeQuote = $this->getHomeQuoteData($lead->uuid);
        if (! $homeQuote) {
            info("sendHomeOCBIntroEmail - HomeQuote not found for Lead Ref ID: {$lead->uuid} | Time: " . now());
            return false;
        }

        // Build email data
        $emailData = $this->buildEmailData(
            $lead,
            $advisor,
            WorkflowTypeEnum::HOME_AUTOMATED_FOLLOWUPS,
            $homeQuote
        );

        // Fetch the automated workflow configuration
        $homeAutomatedEvent = ApplicationStorage::where(
            'key_name',
            ApplicationStorageEnums::HOME_OCB_AUTOMATED_FOLLOWUPS
        )->first();

        if (! $homeAutomatedEvent) {
            info("sendHomeOCBIntroEmail - Workflow configuration not found for Lead Ref ID: {$lead->uuid} | Time: " . now());
            return false;
        }

        try {
            $response = app(BirdService::class)->triggerWebHookRequest($homeAutomatedEvent->value, $emailData);

            if (empty($homeQuote->automated_flow_executed_at)) {
                $homeQuote->update(['automated_flow_executed_at' => now()]);
                info("sendHomeOCBIntroEmail - Automated flow timestamp updated for HomeQuote ID: {$homeQuote->id} | Time: " . now());
            }

            info("sendHomeOCBIntroEmail - Successfully triggered event for Lead Ref ID: {$lead->uuid} | Time: " . now());
            return $response;
        } catch (\Exception $e) {
            info("sendHomeOCBIntroEmail - Error triggering event for Lead Ref ID: {$lead->uuid} | Message: {$e->getMessage()} | Time: " . now());
            return false;
        }
    }



    public function buildEmailData($lead, $advisor, $workflowType, $homeQuote)
    {
        return (object) [
            // Lead-related data
            'quoteUID' => $lead->uuid,
            'uuid' => $lead->uuid,
            'customerEmail' => $lead->email,
            'customerFullName' => trim("{$lead->first_name} {$lead->last_name}"),
            'customerName' => trim("{$lead->first_name} {$lead->last_name}"),
            'refID' => $lead->code,
            'customerMobile' => $lead->mobile_no ?? '',
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::HOME, $lead->uuid),
            'flowExecutedAt' => $lead->flow_executed_at ?? null,

            // Home quote-related data
            'automatedFlowExecuted' => !empty($homeQuote?->automated_flow_executed_at),

            // Advisor-related data
            'advisorId' => $advisor?->id,
            'advisorName' => $advisor?->name ?? '',
            'advisorEmail' => $advisor?->email ?? '',
            'advisorDetails' => $advisor ?? null,
            'landLine' => $advisor?->landline_no ?? '',
            'mobilePhone' => $advisor?->mobile_no ?? '',
            'whatsAppNumber' => $advisor?->mobile_no ? formatMobileNo($advisor->mobile_no) : '',
            'mobileNoWithoutSpaces' => $advisor?->mobile_no ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : '',

            // Workflow-related data
            'workflowType' => $workflowType,
        ];
    }


    public function getHomeQuoteData(string $uuid): ?HomeQuote
    {
        return HomeQuote::with('subArea:id,description')
            ->where('uuid', $uuid)
            ->first();
    }
}
