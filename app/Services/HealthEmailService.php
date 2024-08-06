<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\User;
use App\Enums\quoteTypeCode;
use App\Enums\LeadSourceEnum;
use App\Services\BirdService;
use App\Models\ApplicationStorage;
use App\Enums\ApplicationStorageEnums;;
use App\Services\SendEmailCustomerService;



class HealthEmailService extends BaseService
{
    protected $sendEmailCustomerService;
    protected $birdService;


    public function __construct(SendEmailCustomerService $sendEmailCustomerService,BirdService $birdService)
    {
        $this->sendEmailCustomerService = $sendEmailCustomerService;
        $this->birdService = $birdService;
    }

    public function sendHealthOCBIntroEmail($plans, $lead, $previousAdvisorId, $healthQuoteService, $triggerSICWorkFlow = false)
    {

        $advisor = User::where('id', $lead->advisor_id)->first();

        $emailData = $this->mappingEmailDataForOCBEmail($lead,$advisor,$plans);
        if ($triggerSICWorkFlow) {
            if (! $lead->sic_flow_enabled) {
                $sicEventName = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_SIC_HEALTH_WORKFLOW)->first();

                info('SIC Health workflow key: '.$sicEventName->value);
                if ($sicEventName) {
                    $apiResponse = $this->birdService->sendSICHealthWorkFlow($emailData);
                    $lead->sic_flow_enabled = true;
                    $lead->save();
                    info('SIC Health workflow event triggered for lead: '.$lead->uuid.' and sic_flow_enabled: '.$lead->sic_flow_enabled);
                    info('SIC Health workflow response: '.$apiResponse);
                } else {
                    info('SIC Health workflow key not found');
                }
            } else {
                info('SIC Health workflow already enabled for lead: '.$lead->uuid);
            }
        }

        $responseCode = $this->birdService->sendHealthOCBEmail($emailData);
        info('sic sendHealthOCBEmail - Ref ID:'.$lead->uuid.' Time: '.now());
        return $responseCode;
    }

    public function mappingEmailDataForOCBEmail($lead,$advisor,$plans){
        return (object) [
            'healthQuoteId' => $lead->code,
            'customerEmail' => $lead->email,
            'uuid'=> $lead->uuid,
            'customerFullName' =>  $lead->first_name.' '.$lead->last_name,
            'advisorId' => $advisor->id ?? null,
            'advisorName' =>(! empty($advisor->name) ? $advisor->name : ''),
            'advisorEmail' => (! empty($advisor->email) ? $advisor->email : ''),
            'advisorDetails' => $advisor ?? null,
            'quotePlanLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$lead->uuid,
            'requestAdvisorLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$lead->uuid.'/?assignAdvisor=true',
            'plans' => $plans,
        ];
    }


}
