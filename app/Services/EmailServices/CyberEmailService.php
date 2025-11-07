<?php

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\PersonalQuote;
use App\Models\User;
use App\Services\BaseService;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;

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
        } else {
            LoggerService::info("sendCyberOCBIntroEmail - Error triggering event having response status code: {$response?->status_code}");
        }
    }

    private function buildEmailData($lead, $advisor, $workflowType)
    {
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
            'workflowType' => $workflowType,

        ];
    }

    public function sendCyberAutomatedFollowups($lead)
    {
        $workflowUrl = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_CYBER_AUTOMATED_FOLLOWUPS)->first();

        LoggerService::info('| sendCyberAutomatedFollowups - Initiating process');

        if ($workflowUrl && ! empty($workflowUrl->value)) {
            // Fetch the advisor
            $advisor = User::find($lead->advisor_id);
        }

        if (! $advisor) {
            LoggerService::info('sendCyberAutomatedFollowups - Advisor not found');
        }

        $emailData = $this->buildEmailData($lead, $advisor, WorkflowTypeEnum::CYBER_AUTOMATED_FOLLOWUPS);

        $response = app(BirdService::class)->triggerWebHookRequest($workflowUrl->value, $emailData);
        
        if ($response && $response->status_code === 200) {
            LoggerService::info('sendCyberAutomatedFollowups - Successfully triggered event');
        } else {
            LoggerService::info("sendCyberAutomatedFollowups - Error triggering event having response status code: {$response?->status_code}");
        }
    }


}