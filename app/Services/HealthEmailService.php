<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use App\Models\User;

class HealthEmailService extends BaseService
{
    public function sendHealthOCBIntroEmail($lead, $triggerSICWorkFlow)
    {
        // Retrieve plans with available ratings for the given lead
        info("sic sendHealthOCBEmail - Ref ID: {$lead->uuid}| Time: ".now());
        if ($triggerSICWorkFlow) {
            if (! $lead->sic_flow_enabled) {
                $advisor = User::where('id', $lead->advisor_id)->first();
                $emailData = $this->mapDataForFollowupEmail($lead, $advisor);
                $sicEvent = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_SIC_HEALTH_WORKFLOW)->first();
                if ($sicEvent) {
                    $responseCode = app(BirdService::class)->triggerWebHookRequest($sicEvent->value, $emailData);
                    $lead->sic_flow_enabled = true;
                    $lead->save();
                    info("SIC Health workflow event triggered for lead  Ref-ID: {$lead->uuid} |Time: ".now());
                    info("SIC Health workflow response: {$responseCode} | Ref-ID: {$lead->uuid} |Time: ".now());
                } else {
                    info("SIC Health workflow key not found for lead : Ref-ID: {$lead->uuid} |Time: ".now());
                }
            } else {
                info("SIC Health workflow already enabled for lead Ref-ID: {$lead->uuid} | Time: ".now());
            }
        } else {
            info("triggerSICWorkFlow: {$triggerSICWorkFlow} | - SIC Health workflow not enabled for lead Ref-ID: {$lead->uuid} | Time: ".now());
        }

        return $responseCode ?? null;
    }

    private function mapDataForFollowupEmail($lead, $advisor)
    {
        return (object) [
            'quoteUID' => $lead->uuid,
            'customerEmail' => $lead->email,
            'refID' => $lead->code,
            'uuid' => $lead->uuid,
            'customerFullName' => $lead->first_name.' '.$lead->last_name,
            'advisorId' => $advisor->id ?? null,
            'advisorName' => (! empty($advisor->name) ? $advisor->name : ''),
            'advisorEmail' => (! empty($advisor->email) ? $advisor->email : ''),
            'advisorDetails' => $advisor ?? null,
            'quotePlanLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$lead->uuid,
            'requestAdvisorLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$lead->uuid.'/?assignAdvisor=true',
            'quotePlanApiLink' => config('constants.KEN_API_ENDPOINT').'/get-health-quote-plans-order-priority?'.$lead->uuid.'&lang=en&isModified=true',
            'ApiToken' => config('constants.KEN_API_TOKEN'),
            'basicAuth' => 'Basic '.base64_encode(config('constants.KEN_API_USER').':'.config('constants.KEN_API_PWD')),
            'landLine' => (! empty($advisor->landline_no) ? $advisor->landline_no : ''),
            'mobilePhone' => (! empty($advisor->mobile_no) ? $advisor->mobile_no : ''),
            'whatsAppNumber' => ! empty($advisor->mobile_no) ? formatMobileNo($advisor->mobile_no) : '',
            'mobileNoWithoutSpaces' => (! empty($advisor->mobile_no) ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : ''),
        ];
    }
}
