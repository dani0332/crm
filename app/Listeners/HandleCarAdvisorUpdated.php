<?php

namespace App\Listeners;

use App\Events\CarAdvisorUpdated;
use App\Services\CarQuoteService;
use App\Services\SendSmsCustomerService;

class HandleCarAdvisorUpdated
{
    protected $carQuoteService;
    protected $smsService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(CarQuoteService $carQuoteService, SendSmsCustomerService $smsService)
    {
        $this->carQuoteService = $carQuoteService;
        $this->smsService = $smsService;
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(CarAdvisorUpdated $event)
    {
        $lead = $event->lead;
        // Trigger emails

        //$this->smsService->sendSMS();

    }
    public function buildSMS($lead)
    {
        $content = 'Hi ';
        $content .= $lead->firstname;
    }
}
