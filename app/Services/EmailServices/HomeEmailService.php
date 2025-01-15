<?php

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\User;
use App\Services\BaseService;
use App\Services\BirdService;

class HomeEmailService extends BaseService
{
    public function sendHomeOCBIntroEmail($lead)
    {
        if (! $lead) {
            info('sendHomeOCBIntroEmail - Lead not found for  | Time: '.now());

            return false;
        }

        info("sending sendHomeOCBIntroEmail - Ref ID: {$lead->uuid}| Time: ".now());

        $advisor = User::where('id', $lead->advisor_id)->first();
        $emailData = $this->buildEmailData($lead, $advisor, WorkflowTypeEnum::HOME_AUTOMATED_FOLLOWUPS);
        $homeAutomatedEvent = ApplicationStorage::where('key_name', ApplicationStorageEnums::HOME_OCB_AUTOMATED_FOLLOWUPS)->first();
        if ($homeAutomatedEvent) {
            $response = app(BirdService::class)->triggerWebHookRequest($homeAutomatedEvent->value, $emailData);
            if (! $lead->automated_flow_executed_at) {
                $lead->automated_flow_executed_at = now();
                $lead->save();
            }
            info("sendHomeOCBIntroEmail event triggered for lead  Ref-ID: {$lead->uuid} |Time: ".now());
        } else {
            info("sendHomeOCBIntroEmail workflow key not found for lead : Ref-ID: {$lead->uuid} |Time: ".now());
        }

        return $response ?? null;
    }

    public function buildEmailData($lead, $advisor, $workflowType)
    {
        return (object) [
            'quoteUID' => $lead->uuid,
            'customerEmail' => $lead->email,
            'refID' => $lead->code,
            'automatedFlowExecuted' => empty($lead->automated_flow_executed_at) ? true : false,
            'uuid' => $lead->uuid,
            'customerFullName' => "{$lead->first_name} {$lead->last_name}",
            'customerName' => "{$lead->first_name} {$lead->last_name}",
            'advisorId' => $advisor?->id ?? null,
            'advisorName' => $advisor?->name ?? '',
            'advisorEmail' => $advisor?->email ?? '',
            'advisorDetails' => $advisor ?? null,
            'flowExecutedAt' => $lead->flow_executed_at ?? null,
            'landLine' => (! empty($advisor?->landline_no) ? $advisor->landline_no : ''),
            'mobilePhone' => (! empty($advisor?->mobile_no) ? $advisor->mobile_no : ''),
            'whatsAppNumber' => ! empty($advisor?->mobile_no) ? formatMobileNo($advisor->mobile_no) : '',
            'mobileNoWithoutSpaces' => (! empty($advisor?->mobile_no) ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : ''),
            'workflowType' => $workflowType,
            'customerMobile' => (! empty($lead->mobile_no) ? $lead->mobile_no : ''),
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::HOME, $lead->uuid),
        ];
    }
}
