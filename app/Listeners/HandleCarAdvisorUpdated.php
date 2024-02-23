<?php

namespace App\Listeners;

use App\Enums\LeadSourceEnum;
use App\Events\CarQuoteAdvisorUpdated;
use App\Jobs\SendOCBIntroEmailJob;
use App\Models\Customer;
use App\Models\User;
use App\Services\CarAllocationService;
use App\Services\CarEmailService;
use App\Services\HttpRequestService;
use App\Services\SendSmsCustomerService;
use App\Services\SIBService;

class HandleCarAdvisorUpdated
{
    protected $carQuoteService;
    protected $smsService;
    protected $carEmailService;
    protected $httpService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(CarAllocationService $carQuoteService, SendSmsCustomerService $smsService, CarEmailService $carEmailService, HttpRequestService $httpService)
    {
        $this->carQuoteService = $carQuoteService;
        $this->smsService = $smsService;
        $this->carEmailService = $carEmailService;
        $this->httpService = $httpService;
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(CarQuoteAdvisorUpdated $event)
    {
        info('inside handle car update advisor');

        $lead = $event->lead;

        if ($lead->source == LeadSourceEnum::RENEWAL_UPLOAD) {
            info('lead is source is renewal upload. Skipping intro email job');

            return;
        }

        $oldAdvisorId = $event->oldAdvisorId;

        $previousAdvisor = User::where('id', $oldAdvisorId)->first();

        info('about to trigger intro email job for lead uuid : '.$lead->uuid.' and previous advisor id : '.$oldAdvisorId);

        SendOCBIntroEmailJob::dispatch($lead->uuid, $previousAdvisor);

        if ($lead->sic_flow_enabled) {
            // We need to trigger stop workflow event for SIC if the lead is in SIC workflow
            SIBService::createWorkflowEvent(config('constants.SIC_END_WORKFLOW_NAME'), $lead);
        }

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
