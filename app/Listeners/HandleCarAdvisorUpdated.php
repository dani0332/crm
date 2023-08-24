<?php

namespace App\Listeners;

use App\Enums\quoteTypeCode;
use App\Events\CarQuoteAdvisorUpdated;
use App\Jobs\IntroEmailJob;
use App\Models\Customer;
use App\Services\CarAllocationService;
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
    public function __construct(CarAllocationService $carQuoteService, SendSmsCustomerService $smsService)
    {
        $this->carQuoteService = $carQuoteService;
        $this->smsService = $smsService;
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

        //$this->triggerCarQuoteEmail($lead);
        info('Email sending code reached');

        //$this->buildSMS($lead);
        info('SMS sending code reached');

    }
    public function buildSMS($lead)
    {

        info('inside buidl sms ');
        $content = 'Hi ';
        $clientNumber = '+923340555850';
        $customer = Customer::where('id', 19811)->first();
        $this->smsService->sendSMS($clientNumber, $content, $customer);

        info('inside after build sms');
    }

    public function triggerCarQuoteEmail($lead)
    {
        $emailData = $this->carQuoteService->buildEmailDateForLMSIntroEmail($lead); // create email body for intro email

        $isLeadReassigned = $this->carQuoteService->isLeadReassigned($lead);

        $emailTemplateId = (int) $this->carQuoteService->getAppStorageValueByKey('LMS_INTRO_EMAIL_TEMPLATE_ID'); // template id for LMS intro email

        IntroEmailJob::dispatch(quoteTypeCode::Car, $emailTemplateId, $emailData, 'send-lms-intro-email');
    }
}
