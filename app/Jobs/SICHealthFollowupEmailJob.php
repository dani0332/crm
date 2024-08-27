<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use App\Models\HealthQuote;
use App\Models\User;
use App\Services\BirdService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SICHealthFollowupEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    private $quoteUuid;

    public $tries = 3;
    public $timeout = 15;
    public $backoff = 60;

    public function __construct($quoteUuid)
    {
        $this->quoteUuid = $quoteUuid;
    }

    /**
     * Execute the job.
     */
    public function handle(BirdService $birdService)
    {
        $sicEventName = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_SIC_HEALTH_WORKFLOW)->first();
        $lead = HealthQuote::where('uuid', $this->quoteUuid)->first();
        if (empty($lead)) {
            info('SICHealthFollowupEmailJob - Lead not found forRef-ID: '.$this->quoteUuid.' | Time: '.now());

            return;
        }
        $advisor = User::where('id', $lead->advisor_id)->first();
        $emailData = $this->mappingEmailDataForFollowupEmail($lead, $advisor);

        info('SIC Health workflow key: '.$sicEventName->value.' for lead: Ref-ID: '.$lead->uuid.' | Time: '.now());
        if (! $lead->sic_flow_enabled) {
            if ($sicEventName) {
                $apiResponse = $birdService->sendSICHealthWorkFlow($emailData);
                $lead->sic_flow_enabled = true;
                $lead->save();
                info('SIC Health workflow event triggered for lead: '.$lead->uuid.' and sic_flow_enabled: Ref-ID'.$lead->sic_flow_enabled.'|Time: '.now());
                info('SIC Health workflow response: '.$apiResponse.' | Ref-ID'.$lead->sic_flow_enabled.'|Time: '.now());
            } else {
                info('SIC Health workflow key not found for lead : Ref-ID: '.$lead->uuid.' |Time: '.now());
            }
        } else {
            info('SIC Health workflow already enabled for lead: : Ref-ID: '.$lead->uuid.' |Time: '.now());
        }

    }

    public function mappingEmailDataForFollowupEmail($lead, $advisor)
    {
        return (object) [
            'quoteUID' => $lead->uuid,
            'customerEmail' => $lead->email,
            'uuid' => $lead->uuid,
            'refID' => $lead->code,
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
