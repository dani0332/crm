<?php

namespace App\Services\EmailServices;

use App\Enums\QuoteFlowType;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Jobs\SendDeviceAutomatedFollowupJob;
use App\Models\User;
use App\Services\BaseService;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Str;

class DeviceEmailService extends BaseService
{
    public function sendDeviceOCBIntroEmail($lead)
    {

        LoggerService::info('| sendDeviceOCBIntroEmail - Initiating process');

        // Fetch the advisor
        $advisor = User::find($lead->advisor_id);

        if (empty($advisor)) {
            LoggerService::info('sendDeviceOCBIntroEmail - Advisor not found');
        }

        $emailData = $this->buildEmailData($lead, $advisor, WorkflowTypeEnum::DEVICE_OCB_INTRO_EMAIL);
        $response = app(WebEngageService::class)->sendEvent(WorkflowTypeEnum::DEVICE_OCB_INTRO_EMAIL, (array) $emailData);

        if ($response) {
            LoggerService::info('sendDeviceOCBIntroEmail - Successfully triggered event');
            $isFollowupExecuted = app(WebEngageService::class)->isFollowupExecuted($lead->uuid, QuoteTypes::DEVICE->id(), QuoteFlowType::DEVICE_AUTOMATED_FOLLOWUPS->value);
            if (! $isFollowupExecuted) {
                SendDeviceAutomatedFollowupJob::dispatch($lead->uuid)->delay(now()->addSeconds(10));
                LoggerService::info('sendDeviceOCBIntroEmail - Successfully dispatched cyber automated followup job');
            }

            app(WebEngageService::class)->createQuoteWorkFlowDetails($lead->uuid, QuoteFlowType::DEVICE_OCB_INTRO_EMAIL->value, QuoteTypes::DEVICE->id());
            LoggerService::info('sendDeviceOCBIntroEmail - Successfully created quote flow details');
            if (getWhatsappConsent(QuoteTypes::DEVICE, $lead->uuid)) {
                app(WebEngageService::class)->createQuoteWhatsAppFlowDetails($lead, WorkflowTypeEnum::DEVICE_OCB_INTRO_WHATSAPP, QuoteTypes::DEVICE->id());
                LoggerService::info('sendDeviceOCBIntroEmail - Successfully created quote whatsapp flow details');
            }
        }
    }

    private function buildEmailData($lead, $advisor, $workflowType)
    {
        $isFlowExecuted = app(WebEngageService::class)->isFollowupExecuted($lead->uuid, QuoteTypes::DEVICE->id(), QuoteFlowType::DEVICE_OCB_INTRO_EMAIL->value);

        return [
            'uniqueId' => (string) Str::ulid(),
            'customerId' => $lead->customer_id ?? '',
            'firstName' => $lead->first_name ?? '',
            'lastName' => $lead->last_name ?? '',
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
        $isFollowupExecuted = app(WebEngageService::class)->isFollowupExecuted($lead->uuid, QuoteTypes::DEVICE->id(), QuoteFlowType::DEVICE_AUTOMATED_FOLLOWUPS->value);
        if ($isFollowupExecuted) {
            LoggerService::info('sendDeviceAutomatedFollowups - Followup already executed');

            return;
        }

        LoggerService::info('| sendDeviceAutomatedFollowups - Initiating process');

        // Fetch the advisor
        $advisor = User::find($lead->advisor_id);
        if (! $advisor) {
            LoggerService::info('sendDeviceAutomatedFollowups - Advisor not found');
        }

        $emailData = $this->buildEmailData($lead, $advisor, WorkflowTypeEnum::DEVICE_AUTOMATED_FOLLOWUPS);

        $response = app(WebEngageService::class)->sendEvent(WorkflowTypeEnum::DEVICE_AUTOMATED_FOLLOWUPS, (array) $emailData);

        if ($response) {
            LoggerService::info('sendDeviceAutomatedFollowups - Successfully triggered event');
            app(WebEngageService::class)->createQuoteWorkFlowDetails($lead->uuid, QuoteFlowType::DEVICE_AUTOMATED_FOLLOWUPS->value, QuoteTypes::DEVICE->id());
            LoggerService::info('sendDeviceAutomatedFollowups - Successfully created quote flow details ');
        } else {
            LoggerService::info("sendDeviceAutomatedFollowups - Error triggering event having response status code: {$response?->status_code}");
        }
    }

    public function sendZeroPlansEmail($lead)
    {
        try {

            $advisor = User::find($lead->advisor_id);

            $emailData = $this->buildEmailData($lead, $advisor, WorkflowTypeEnum::DEVICE_ZERO_PLANS_EMAIL);
            $response = app(WebEngageService::class)->sendEvent(WorkflowTypeEnum::DEVICE_ZERO_PLANS_EMAIL, (array) $emailData);

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
