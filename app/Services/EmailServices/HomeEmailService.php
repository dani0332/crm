<?php

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\User;
use App\Services\BaseService;
use App\Services\BirdService;
use App\Models\HomeQuote;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\RenewalsBatchEmails;
use App\Models\RenewalQuoteProcess;

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

    public function sendRenewalOCBEmail($batch, RenewalsBatchEmails $renewalsBatchEmail, RenewalQuoteProcess $renewalQuoteProcess)
    {
        try {
            // Find Home Quote
            $lead = HomeQuote::find($renewalQuoteProcess->quote_id);
            
            info('Home Renewals OCB Email started for uuid: '.$lead->uuid);

            // Get Lead Advisor
            $advisor = User::where('id', $lead->advisor_id)->first();

            // Map Data for Home OCB Email
            $emailData = $this->mapDataForRenewalOCBEmail($lead, $advisor, WorkflowTypeEnum::HOME_RENEWAL_OCB);

            $workflowUrl = ApplicationStorage::where('key_name', WorkflowTypeEnum::HOME_RENEWAL_OCB)->first()?->value;
            
            if($workflowUrl){
                app(BirdService::class)->triggerWebHookRequest($workflowUrl, $emailData);
                info('Renewals OCB Email completed for uuid: '.$lead->uuid);

                RenewalsBatchEmails::where('id', $renewalsBatchEmail->id)->update(['total_sent' => DB::raw('total_sent+1')]);
                RenewalQuoteProcess::where('id', $renewalQuoteProcess->id)->update(['email_sent' => 1]);

            }else{
                info('Home Renewals OCB Email failed error: Workflow URL not found');
            }
            
        } catch (\Exception $exception) {
            Log::info('Renewals OCB Email failed error: '.$exception->getMessage());
            RenewalsBatchEmails::where('id', $renewalsBatchEmail->id)->update(['total_failed' => DB::raw('total_failed+1')]);
        }
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
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::HOME, uuid: $lead->uuid),
        ];
    }

    private function mapDataForRenewalOCBEmail($lead, $advisor, $workflowType)
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
            // 'whatsappConsent' => getWhatsappConsent(QuoteTypes::HOME, uuid: $lead->uuid),
        ];
    }
}
