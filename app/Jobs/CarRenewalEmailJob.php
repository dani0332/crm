<?php

namespace App\Jobs;

use App\Enums\TiersEnum;
use App\Models\CarQuote;
use App\Models\Tier;
use App\Models\User;
use App\Services\EmailDataService;
use App\Services\LeadAllocationService;
use App\Services\SIBService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CarRenewalEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 15;
    public $backoff = 300;
    private $lead = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($lead)
    {
        $this->lead = $lead;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(EmailDataService $emailDataService, LeadAllocationService $leadAllocationService)
    {
        info('sendRenewalLeadEmail -- start');

        $renewalEmailRecipients = $leadAllocationService->getAppStorageValueByKey('RENEWAL_ALLOCATION_LEAD_EMAIL_RECIPIENTS');

        if (isset($this->lead->advisor_id)) {
            $renewalEmailRecipients .= ','.User::where('id', $this->lead->advisor_id)->first()->email;
        }

        $renewalEmailCcRecipients = $leadAllocationService->getAppStorageValueByKey('RENEWAL_ALLOCATION_LEAD_EMAIL_CC');

        $emailData = $emailDataService->generateTierREmailData($this->lead);

        $templateId = (int) $leadAllocationService->getAppStorageValueByKey('CAR_RENEWAL_ALLOCATION_LEAD_EMAIL_TEMPLATE_ID');

        $tag = config('constants.APP_ENV').' - motor allocation renewal';

        info('sendRenewalLeadEmail -- start sending email for lead : '.$this->lead->uuid);

        SIBService::sendEmailUsingSIB($templateId, $emailData, $tag, $renewalEmailRecipients, $renewalEmailCcRecipients);

        info('sendRenewalLeadEmail -- email sending done for lead : '.$this->lead->uuid);

        CarQuote::where('id', $this->lead->id)->update([
            'is_renewal_tier_email_sent' => 1,
        ]);
        info('sendRenewalLeadEmail -- end');

        $tier = Tier::where('name', TiersEnum::TIER_R)->where('is_active', 1)->first();
        if ($tier) {
            info('setting tier : '.$tier->name.' against car lead : '.$this->lead->uuid);
            $this->lead->tier_id = $tier->id;
            $this->lead->save();
        } else {
            info('tier R for sending email is not found');
        }
        info('Renewal Email sent for quote : '.$this->lead->uuid.' and tier is update with id : '.$tier->id);
    }
}
