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
use Carbon\Carbon;

class CyberEmailService extends BaseService
{
    public function sendCyberOCBIntroEmail($lead, $previousAdvisor = null, bool $triggerSICWorkflow = false, bool $handleZeroPlans = false, bool $forceSicWorkflow = false)
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

        if (! $workflowUrl || empty($workflowUrl->value)) {
            LoggerService::info('sendCyberOCBIntroEmail - Workflow URL not found or empty');

            return;
        }

        $emailData = $this->buildEmailData($lead, $advisor, WorkflowTypeEnum::CYBER_OCB_INTRO_EMAIL, $previousAdvisor);

        // Handle SIC workflow if requested
        // Note: Cyber doesn't have sic_flow_enabled on PersonalQuote like Travel/Car,
        // but we accept the parameters for consistency with the interface
        if ($triggerSICWorkflow || $forceSicWorkflow) {
            LoggerService::info(self::class." - SIC workflow trigger requested for Cyber lead: {$lead->uuid} (triggerSICWorkflow: {$triggerSICWorkflow}, forceSicWorkflow: {$forceSicWorkflow})");
            // Cyber uses sic_advisor_requested on cyber_quote relation instead of sic_flow_enabled
            // If SIC workflow infrastructure is added for Cyber in the future, it should be implemented here
        }

        // Note: handleZeroPlans parameter is accepted for consistency but Cyber doesn't have plans like Travel
        if ($handleZeroPlans) {
            LoggerService::info(self::class." - handleZeroPlans flag set for Cyber lead: {$lead->uuid} (not applicable to Cyber quotes)");
        }

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

    private function buildEmailData($lead, $advisor, $workflowType, $previousAdvisor = null)
    {

        $isFlowExecuted = app(BirdService::class)->isFollowupExecuted($lead->uuid, QuoteTypes::CYBER->id(), QuoteFlowType::CYBER_OCB_INTRO_EMAIL->value);
        $isMinor = ! empty($lead->dob) ? Carbon::parse($lead->dob)->age < 18 : false;

        $emailData = [
            'advisorEmail' => (! empty($advisor->email) ? $advisor->email : ''),
            'source' => $lead->source,
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
            'isMinor' => $isMinor,
            'workflowType' => $workflowType,
            'isReAssignment' => ! empty($previousAdvisor),
        ];

        // Add previous advisor details if available
        if (! empty($previousAdvisor)) {
            $emailData['previousAdvisorName'] = ! empty($previousAdvisor->name) ? $previousAdvisor->name : '';
            $emailData['previousAdvisorEmail'] = ! empty($previousAdvisor->email) ? $previousAdvisor->email : '';
            $emailData['previousAdvisorMobilePhone'] = ! empty($previousAdvisor->mobile_no) ? $previousAdvisor->mobile_no : '';
        }

        return $emailData;
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
