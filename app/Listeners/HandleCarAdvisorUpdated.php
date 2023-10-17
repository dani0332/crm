<?php

namespace App\Listeners;

use App\Events\CarQuoteAdvisorUpdated;
use App\Jobs\SendOCBIntroEmailJob;
use App\Models\Customer;
use App\Models\User;
use App\Services\CarAllocationService;
use App\Services\CarEmailService;
use App\Services\HttpRequestService;
use App\Services\SendSmsCustomerService;

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

        $oldAdvisorId = $event->oldAdvisorId;

        $previousAdvisor = User::where('id', $oldAdvisorId)->first();

        info('about to trigger intro email job for lead uuid : '.$lead->uuid.' and previous advisor id : '.$oldAdvisorId);

        SendOCBIntroEmailJob::dispatch($lead->uuid, $previousAdvisor)->onQueue('renewals');

        info('SMS sending code reached');

        //buildSMS($lead);

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
