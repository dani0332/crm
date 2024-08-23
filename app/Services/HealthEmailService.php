<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use App\Models\User;

class HealthEmailService extends BaseService
{
    protected $birdService;
    protected $healthQuoteService;

    public function __construct()
    {
        $this->birdService = new BirdService;
    }
    public function triggerOCAFollowups($lead)
    {
        info('Sending OCA Health followups email for lead: '.$lead->uuid.' | Time: '.now());
        if (! $lead->oca_flow_enabled) {
            $advisor = User::where('id', $lead->advisor_id)->first();
            $emailData = $this->mappingEmailDataForOCAEmail($lead, $advisor);
            $eventName = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_OCA_HEALTH_WORKFLOW)->first();
            info('OCA Health workflow key: '.$eventName->value);
            if ($eventName) {
                if (! empty($emailData)) {
                    info('sendOCAHealthWorkFlow: OCA Health followups email for lead: '.$lead->uuid.' | time: '.now());
                    $responseCode = $this->birdService->sendOCAHealthWorkFlow($emailData);
                } else {
                    $responseCode = null;
                }
                $lead->oca_flow_enabled = true;
                $lead->save();
                info('OCA Health workflow event triggered for lead: '.$lead->uuid.' and oca_flow_enabled: '.$lead->oca_flow_enabled);
                info('OCA Health workflow response: '.$responseCode);
            } else {
                info('OCA Health workflow key not found');
            }
        } else {
            info('OCA Health workflow already enabled for lead: '.$lead->uuid);
        }

        return $responseCode ?? null;
    }
    public function triggerPendingHealthFollowupEmails($lead)
    {
        // Retrieve plans with available ratings for the given lead

        info('triggerPendingHealthFollowupEmails: Sending AppPending Health followups email for lead: '.$lead->uuid.' | time: '.now());
        $lead->pending_flow_enabled = false;
        if (! $lead->pending_flow_enabled) {
            $advisor = User::where('id', $lead->advisor_id)->first();
            $emailData = $this->mappingEmailDataForOCAEmail($lead, $advisor);
            $eventName = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_APP_PENDING_HEALTH_WORKFLOW)->first();
            info('triggerPendingHealthFollowupEmails Health workflow key: '.$eventName->value);
            if ($eventName) {
                $responseCode = $this->birdService->sendAppPendingHealthWorkFlow($emailData);
                $lead->pending_flow_enabled = true;
                $lead->save();
                info('triggerPendingHealthFollowupEmails: Health workflow event triggered for lead: '.$lead->uuid.' and oca_flow_enabled: '.$lead->oca_flow_enabled);
                info('triggerPendingHealthFollowupEmails: Health workflow response: '.$responseCode);
            } else {
                info('triggerPendingHealthFollowupEmails: Health workflow key not found');
            }
        } else {
            info('triggerPendingHealthFollowupEmails: Health workflow already enabled for lead: '.$lead->uuid);
        }

        return $responseCode ?? null;
    }

    public function mappingEmailDataForOCAEmail($lead, $advisor)
    {
        return (object) [
            'quoteUID' => $lead->code,
            'customerEmail' => $lead->email,
            'refID' => $lead->uuid,
            'customerName' => $lead->first_name.' '.$lead->last_name,
            'advisorId' => $advisor->id ?? null,
            'advisorName' => (! empty($advisor->name) ? $advisor->name : ''),
            'advisorEmail' => (! empty($advisor->email) ? $advisor->email : ''),
            'advisorDetails' => $advisor ?? null,
            'quotePlanLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$lead->uuid,
            'requestAdvisorLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$lead->uuid.'/?assignAdvisor=true',
            'landLine' => (! empty($advisor->landline_no) ? $advisor->landline_no : ''),
            'mobilePhone' => (! empty($advisor->mobile_no) ? $advisor->mobile_no : ''),
            'whatsAppNumber' => ! empty($advisor->mobile_no) ? formatMobileNo($advisor->mobile_no) : '',
            'mobileNoWithoutSpaces' => (! empty($advisor->mobile_no) ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : ''),
        ];
    }

}
