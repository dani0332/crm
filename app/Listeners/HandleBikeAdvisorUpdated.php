<?php

namespace App\Listeners;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypes;
use App\Events\BikeQuoteAdvisorUpdated;
use App\Jobs\SendFTCEmailJob;
use App\Jobs\SendOCBIntroEmailForBikeJob;
use App\Models\Customer;
use App\Models\User;
use App\Services\BikeAllocationService;
use App\Services\BikeEmailService;
use App\Services\HttpRequestService;
use App\Services\SendSmsCustomerService;

class HandleBikeAdvisorUpdated
{
    protected $bikeQuoteService;
    protected $smsService;
    protected $bikeEmailService;
    protected $httpService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(BikeAllocationService $bikeQuoteService, SendSmsCustomerService $smsService, BikeEmailService $bikeEmailService, HttpRequestService $httpService)
    {
        $this->bikeQuoteService = $bikeQuoteService;
        $this->smsService = $smsService;
        $this->bikeEmailService = $bikeEmailService;
        $this->httpService = $httpService;
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(BikeQuoteAdvisorUpdated $event)
    {
        info('inside handle Bike Update Advisor event listener');

        $lead = $event->lead;

        if ($lead) {
            info('about to dispatch SendFTCEmailJob for lead uuid : '.$lead->uuid);
            SendFTCEmailJob::dispatch($lead->uuid, QuoteTypes::BIKE)->delay(now()->addSeconds(5));
        }

        $skippableSources = [LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::INSLY];
        if (in_array($lead->source, $skippableSources)) {
            info('lead is source is '.$lead->source.' upload. Skipping intro email job');

            return;
        }

        $oldAdvisorId = $event->oldAdvisorId;

        $previousAdvisor = User::where('id', $oldAdvisorId)->first();

        info('about to trigger intro email job for lead uuid : '.$lead->uuid.' and previous advisor id : '.$oldAdvisorId);

        SendOCBIntroEmailForBikeJob::dispatch($lead->uuid, $previousAdvisor);

        info('SMS sending code reached');
    }
    public function buildSMS($lead)
    {
        info('inside build sms');
        $content = 'Hi ';
        $clientNumber = '+923340555850';
        $customer = Customer::where('id', 19811)->first();
        $this->smsService->sendSMS($clientNumber, $content, $customer);

        info('inside after build sms');
    }
}
