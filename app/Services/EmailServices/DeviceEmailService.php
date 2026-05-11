<?php

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Jobs\SendDeviceAutomatedFollowupJob;
use App\Models\ApplicationStorage;
use App\Models\User;
use App\Services\BaseService;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;

class DeviceEmailService extends BaseService
{
    public function sendDeviceOCBIntroEmail($lead)
    {
        $workflowUrl = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_DEVICE_OCB_INTRO_EMAIL)->first();

        LoggerService::info('| sendDeviceOCBIntroEmail - Initiating process');
        $advisor = null;
        if ($workflowUrl && ! empty($workflowUrl->value)) {
            // Fetch the advisor
            $advisor = User::find($lead->advisor_id);
        }

        if (empty($advisor)) {
            LoggerService::info('sendDeviceOCBIntroEmail - Advisor not found');
        }

        $emailData = $this->buildEmailData($lead, $advisor, WorkflowTypeEnum::DEVICE_OCB_INTRO_EMAIL);

        $response = app(BirdService::class)->triggerWebHookRequest($workflowUrl->value, $emailData);

        if ($response && $response->status_code === 200) {
            LoggerService::info('sendDeviceOCBIntroEmail - Successfully triggered event');
            $isFollowupExecuted = app(BirdService::class)->isFollowupExecuted($lead->uuid, QuoteTypes::DEVICE->id(), QuoteFlowType::DEVICE_AUTOMATED_FOLLOWUPS->value);
            if (! $isFollowupExecuted) {
                SendDeviceAutomatedFollowupJob::dispatch($lead->uuid)->delay(now()->addSeconds(10));
                LoggerService::info('sendDeviceOCBIntroEmail - Successfully dispatched cyber automated followup job');
            }

            app(BirdService::class)->createQuoteWorkFlowDetails($lead, $response, QuoteFlowType::DEVICE_OCB_INTRO_EMAIL->value, QuoteTypes::DEVICE->id());
            LoggerService::info('sendDeviceOCBIntroEmail - Successfully created quote flow details');
            if (getWhatsappConsent(QuoteTypes::DEVICE, $lead->uuid)) {
                app(BirdService::class)->createQuoteWhatsAppFlowDetails($lead, WorkflowTypeEnum::DEVICE_OCB_INTRO_WHATSAPP, QuoteTypes::DEVICE->id());
                LoggerService::info('sendDeviceOCBIntroEmail - Successfully created quote whatsapp flow details');
            }
        }

    }

    private function buildEmailData($lead, $advisor, $workflowType)
    {
        $isFlowExecuted = app(BirdService::class)->isFollowupExecuted($lead->uuid, QuoteTypes::DEVICE->id(), QuoteFlowType::DEVICE_OCB_INTRO_EMAIL->value);

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
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::DEVICE, $lead->uuid),
            'isFollowupExecuted' => $isFlowExecuted ? true : false,
            'workflowType' => $workflowType,

        ];
    }

    public function sendDeviceAutomatedFollowups($lead)
    {
        $isFollowupExecuted = app(BirdService::class)->isFollowupExecuted($lead->uuid, QuoteTypes::DEVICE->id(), QuoteFlowType::DEVICE_AUTOMATED_FOLLOWUPS->value);
        if ($isFollowupExecuted) {
            LoggerService::info('sendDeviceAutomatedFollowups - Followup already executed');

            return;
        }
        $workflowUrl = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_DEVICE_OCB_INTRO_EMAIL)->first();

        LoggerService::info('| sendDeviceAutomatedFollowups - Initiating process');

        if ($workflowUrl && ! empty($workflowUrl->value)) {
            // Fetch the advisor
            $advisor = User::find($lead->advisor_id);
        }

        if (! $advisor) {
            LoggerService::info('sendDeviceAutomatedFollowups - Advisor not found');
        }

        $emailData = $this->buildEmailData($lead, $advisor, WorkflowTypeEnum::DEVICE_AUTOMATED_FOLLOWUPS);

        $response = app(BirdService::class)->triggerWebHookRequest($workflowUrl->value, $emailData);

        if ($response && $response->status_code === 200) {
            LoggerService::info('sendDeviceAutomatedFollowups - Successfully triggered event');
            app(BirdService::class)->createQuoteWorkFlowDetails($lead, $response, QuoteFlowType::DEVICE_AUTOMATED_FOLLOWUPS->value, QuoteTypes::DEVICE->id());
            LoggerService::info('sendDeviceAutomatedFollowups - Successfully created quote flow details');
        } else {
            LoggerService::info("sendDeviceAutomatedFollowups - Error triggering event having response status code: {$response?->status_code}");
        }
    }

    public function sendZeroPlansEmail($lead)
    {
        try {

            $advisor = User::find($lead->advisor_id);
            $workflowUrl = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_DEVICE_OCB_INTRO_EMAIL)->first();
            if (! $workflowUrl) {
                return [
                    'success' => false,
                    'message' => 'Workflow URL not found',
                ];
            }
            $emailData = $this->buildEmailData($lead, $advisor, WorkflowTypeEnum::DEVICE_ZERO_PLANS_EMAIL);
            $response = app(BirdService::class)->triggerWebHookRequest($workflowUrl->value, $emailData);

            if ($response) {
                return [
                    'success' => true,
                    'message' => 'Zero plans email sent successfully',
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error sending zero plans email',
                ];
            }
        } catch (\Throwable $th) {
            LoggerService::error(self::class.': Error sending zero plans email', [
                'error' => $th->getMessage(),
                'quote_uuid' => $lead->uuid,
            ]);

            return [
                'success' => false,
                'message' => 'Something went wrong while sending zero plans email for quote ',
            ];
        }
    }
}
