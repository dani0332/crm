<?php

namespace App\Listeners;

use App\Enums\CarPlanType;
use App\Enums\quoteTypeCode;
use App\Enums\TiersEnum;
use App\Events\CarQuoteAdvisorUpdated;
use App\Jobs\IntroEmailJob;
use App\Models\CarQuotePlanDetail;
use App\Models\Customer;
use App\Models\Tier;
use App\Models\User;
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
        $emailData = '';
        $tierR = Tier::where('name', TiersEnum::TIER_R)->where('is_active', 1)->first();
        $plans = CarQuotePlanDetail::where('quote_uuid', $lead->uuid)
            ->where('is_rating_available', true)
            ->where('repair_type', CarPlanType::COMP)->get();

        $emailTemplateId = '';

        if (count($plans) == 0) {
            info('Inside zero plan for sending email');
            $emailData = $this->carQuoteService->buildNoPlansEmailData($lead, $previousAdvisor);
            $emailTemplateId = $lead->tier_id == $tierR->id ? 492 : 494;
        } else {
            info('Inside multiple plan for sending email');
            $emailData = $this->carQuoteService->buildPlansEmailData($lead, $plans, $previousAdvisor);
            $emailTemplateId = $lead->tier_id == $tierR->id ? 491 : 493;
        }

        info('email data is : '.json_encode($emailData));
        info('email template id is : '.json_encode($emailTemplateId));
        IntroEmailJob::dispatch(quoteTypeCode::Car, $emailTemplateId, $emailData, 'lms-intro-email');
    }
}
