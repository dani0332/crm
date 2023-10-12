<?php

namespace App\Listeners;

use App\Enums\quoteTypeCode;
use App\Enums\TiersEnum;
use App\Events\CarQuoteAdvisorUpdated;
use App\Jobs\IntroEmailJob;
use App\Models\Customer;
use App\Models\Tier;
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

        $this->triggerCarQuoteEmail($lead, $previousAdvisor);

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

    public function triggerCarQuoteEmail($lead, $previousAdvisor)
    {
        // Initialize email data and retrieve Tier R information
        $emailData = '';
        $tierR = Tier::where('name', TiersEnum::TIER_R)->where('is_active', 1)->first();

        // Retrieve plans with available ratings for the given lead
        $plans = $this->httpService->getPlans($lead->uuid, true, false, false);

        info('plans: '.json_encode($plans));

        // Determine the email template ID
        $emailTemplateId = $this->getEmailTemplateId($lead, $plans, $tierR);

        // Build email data
        $emailData = $this->buildEmailData($lead, $plans, $previousAdvisor, $tierR->id);

        // Log email data and template ID
        info('Email data: '.json_encode($emailData));

        info('Email template ID: '.json_encode($emailTemplateId));

        // Dispatch an email job to send the email
        IntroEmailJob::dispatch(quoteTypeCode::Car, $emailTemplateId, $emailData, 'lms-intro-email');
    }

    private function getEmailTemplateId($lead, $plans, $tierR)
    {
        if (count($plans) == 0) {
            // No plans with available ratings, send a specific email template
            return $lead->tier_id == $tierR->id ? 492 : 494;
        } else {
            // Plans with available ratings exist, send a different email template
            return $lead->tier_id == $tierR->id ? 491 : 493;
        }
    }

    private function buildEmailData($lead, $plans, $previousAdvisor, $tierRId)
    {
        if (count($plans) == 0) {
            // No plans with available ratings, build email data for the specific case
            return $this->carEmailService->buildNoPlansEmailData($lead, $previousAdvisor, $tierRId);
        } else {
            // Plans with available ratings exist, build email data for the different case
            return $this->carEmailService->buildPlansEmailData($lead, $plans, $previousAdvisor, $tierRId);
        }
    }

}
