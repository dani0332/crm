<?php

namespace App\Services;

use App\Jobs\SICHealthFollowupEmailJob;
use App\Models\User;

class HealthEmailService extends BaseService
{
    protected $birdService;

    public function __construct(BirdService $birdService)
    {
        $this->birdService = $birdService;
    }
    public function sendHealthOCBIntroEmail($lead, $healthQuoteService, $triggerSICWorkFlow = false)
    {
        // Retrieve plans with available ratings for the given lead

        info('sic sendHealthOCBEmail - Ref ID:'.$lead->uuid.'| Time: '.now());
        if ($triggerSICWorkFlow) {
            if (! $lead->sic_flow_enabled) {
                $advisor = User::where('id', $lead->advisor_id)->first();
                $emailData = $this->mappingEmailDataForOCBEmail($lead, $advisor);
                // $responseCode = $this->birdService->sendHealthOCBEmail($emailData);
                // // Dispatch the job with a 30 mint delay
                // SICHealthFollowupEmailJob::dispatch($lead->uuid);
                // info('SIC Health workflow event triggered for lead: Ref-ID: '.$lead->uuid.' |Time: '.now());
                // info('SICHealthFollowupEmailJob Dispatched - Ref ID:'.$lead->uuid.'|  Time: '.now());
                $sicEventName = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_SIC_HEALTH_WORKFLOW)->first();

                if (! $lead->sic_flow_enabled) {
                    if ($sicEventName) {
                        $responseCode = $this->birdService->sendSICHealthWorkFlow($emailData);
                        $lead->sic_flow_enabled = true;
                        $lead->save();
                        info('SIC Health workflow event triggered for lead: '.$lead->uuid.' and sic_flow_enabled: Ref-ID'.$lead->sic_flow_enabled.'|Time: '.now());
                        info('SIC Health workflow response: '.$responseCode.' | Ref-ID'.$lead->sic_flow_enabled.'|Time: '.now());
                    } else {
                        info('SIC Health workflow key not found for lead : Ref-ID: '.$lead->uuid.' |Time: '.now());
                    }
                } else {
                    info('SIC Health workflow already enabled for lead: : Ref-ID: '.$lead->uuid.' |Time: '.now());
                }
            } else {
                info('SIC Health workflow already enabled for lead Ref-ID: '.$lead->uuid.' | Time: '.now());
            }
        } else {
            info('triggerSICWorkFlow:'.$triggerSICWorkFlow.' | - SIC Health workflow not enabled for lead Ref-ID: '.$lead->uuid.' | Time: '.now());
        }

        return $responseCode ?? null;
    }

    public function mappingEmailDataForOCBEmail($lead, $advisor)
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
        ];
    }

}
