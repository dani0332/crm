<?php

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Jobs\SendCyberAutomatedFollowupJob;
use App\Models\ApplicationStorage;
use App\Models\User;
use App\Services\BaseService;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;

class CyberEmailService extends BaseService
{
    public function sendCyberOCBIntroEmail($lead)
    {
        $workflowUrl = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_CYBER_OCB_INTRO_EMAIL)->first();

        LoggerService::info('| sendCyberOCBIntroEmail - Initiating process');
        $advisor = null;
        if ($workflowUrl && ! empty($workflowUrl->value)) {
            // Fetch the advisor
            $advisor = User::find($lead->advisor_id);
        }

        if (empty($advisor)) {
            LoggerService::info('sendCyberOCBIntroEmail - Advisor not found');
        }

        $emailData = $this->buildEmailData($lead, $advisor, WorkflowTypeEnum::CYBER_OCB_INTRO_EMAIL);

        $response = app(BirdService::class)->triggerWebHookRequest($workflowUrl->value, $emailData);

        if ($response && $response->status_code === 200) {
            LoggerService::info('sendCyberOCBIntroEmail - Successfully triggered event');
            $isFollowupExecuted = app(BirdService::class)->isFollowupExecuted($lead->uuid, QuoteTypes::CYBER->id(), QuoteFlowType::CYBER_AUTOMATED_FOLLOWUPS->value);
            if (! $isFollowupExecuted) {
                SendCyberAutomatedFollowupJob::dispatch($lead->uuid)->delay(now()->addSeconds(10));
                LoggerService::info('sendCyberOCBIntroEmail - Successfully dispatched cyber automated followup job');
            }

            app(BirdService::class)->createQuoteWorkFlowDetails($lead, $response, QuoteFlowType::CYBER_OCB_INTRO_EMAIL->value, QuoteTypes::CYBER->id());
            LoggerService::info('sendCyberOCBIntroEmail - Successfully created quote flow details');
            if (getWhatsappConsent(QuoteTypes::CYBER, $lead->uuid)) {
                app(BirdService::class)->createQuoteWhatsAppFlowDetails($lead, WorkflowTypeEnum::CYBER_OCB_INTRO_WHATSAPP, QuoteTypes::CYBER->id());
                LoggerService::info('sendCyberOCBIntroEmail - Successfully created quote whatsapp flow details');
            }
        }

    }

    private function buildEmailData($lead, $advisor, $workflowType)
    {
        $isFlowExecuted = app(BirdService::class)->isFollowupExecuted($lead->uuid, QuoteTypes::CYBER->id(), QuoteFlowType::CYBER_OCB_INTRO_EMAIL->value);

        return [
            'advisorEmail' => (! empty($advisor->email) ? $advisor->email : ''),
            'advisorLandLine' => (! empty($advisor->landline_no) ? $advisor->landline_no : ''),
            'advisorMobilePhone' => (! empty($advisor->mobile_no) ? $advisor->mobile_no : ''),
            'advisorName' => (! empty($advisor->name) ? $advisor->name : ''),
            'advisorProfilePhotoPath' => (! empty($advisor->profile_photo_path) ? $advisor->profile_photo_path : ''),
            'advisorWhatsAppNumber' => ! empty($advisor->mobile_no) ? formatMobileNo($advisor->mobile_no) : '',
            'quoteUID' => $lead->uuid,
            'customerEmail' => $lead->email,
            'customerFullName' => trim("{$lead->first_name} {$lead->last_name}"),
            'customerName' => trim("{$lead->first_name} {$lead->last_name}"),
            'refID' => $lead->code,
            'customerMobile' => $lead->mobile_no ?? '',
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::CYBER, $lead->uuid),
            'isFollowupExecuted' => $isFlowExecuted ? true : false,
            'workflowType' => $workflowType,

        ];
    }

    public function sendCyberAutomatedFollowups($lead)
    {
        $isFollowupExecuted = app(BirdService::class)->isFollowupExecuted($lead->uuid, QuoteTypes::CYBER->id(), QuoteFlowType::CYBER_AUTOMATED_FOLLOWUPS->value);
        if ($isFollowupExecuted) {
            LoggerService::info('sendCyberAutomatedFollowups - Followup already executed');

            return;
        }
        $workflowUrl = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_CYBER_AUTOMATED_FOLLOWUPS)->first();

        LoggerService::info('| sendCyberAutomatedFollowups - Initiating process');
        $advisor = null;
        if ($workflowUrl && ! empty($workflowUrl->value)) {
            // Fetch the advisor
            $advisor = User::find($lead->advisor_id);
        }

        if (! $advisor) {
            LoggerService::info('sendCyberAutomatedFollowups - Advisor not found');
        }

        if (! $workflowUrl || empty($workflowUrl->value)) {
            LoggerService::info('sendCyberAutomatedFollowups - Workflow URL not found or empty');
            return;
        }

        $emailData = $this->buildEmailData($lead, $advisor, WorkflowTypeEnum::CYBER_AUTOMATED_FOLLOWUPS);

        $response = app(BirdService::class)->triggerWebHookRequest($workflowUrl->value, $emailData);

        if ($response && $response->status_code === 200) {
            LoggerService::info('sendCyberAutomatedFollowups - Successfully triggered event');
            app(BirdService::class)->createQuoteWorkFlowDetails($lead, $response, QuoteFlowType::CYBER_AUTOMATED_FOLLOWUPS->value, QuoteTypes::CYBER->id());
            LoggerService::info('sendCyberAutomatedFollowups - Successfully created quote flow details');
        } else {
            LoggerService::info("sendCyberAutomatedFollowups - Error triggering event having response status code: {$response?->status_code}");
        }
    }

}
