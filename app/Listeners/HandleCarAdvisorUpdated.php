<?php

namespace App\Listeners;

use App\Enums\AssignmentTypeEnum;
use App\Enums\CarPlanType;
use App\Enums\quoteTypeCode;
use App\Events\CarQuoteAdvisorUpdated;
use App\Jobs\IntroEmailJob;
use App\Models\CarQuotePlanDetail;
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
        $oldAdvisorId = $event->oldAdvisorId;
        $oldAssignmentType = $event->oldAssignmentType;

        if ($lead->assignment_type == AssignmentTypeEnum::SYSTEM_ASSIGNED || $lead->assignment_type == AssignmentTypeEnum::MANUAL_ASSIGNED) {

        }

        if ($lead->assignment_type == AssignmentTypeEnum::SYSTEM_REASSIGNED || $lead->assignment_type == AssignmentTypeEnum::MANUAL_REASSIGNED) {

        }

        if ($lead->assignment_type === AssignmentTypeEnum::SYSTEM_REASSIGNED) {
            // reassignmenet template
            if ($oldAdvisorId != null && $oldAdvisorId !== $lead->advisor_id()) {
                // reassignmenet template
            } else {
                // assignment template
            }

        } elseif ($lead->assignment_type === AssignmentTypeEnum::SYSTEM_ASSIGNED) {
            // assignment template
        }

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
        $emailData = "";

        $plans = CarQuotePlanDetail::where('quote_uuid', $lead->uuid)
                            ->where('is_rating_available', true)
                            ->where('repair_type', CarPlanType::COMP)->get();

        $emailTemplateId = "";

        if(count($plans) == 0)
        {
            $emailData = $this->carQuoteService->buildNoPlansEmailData($lead);
            $emailTemplateId = 494;
        }
        else if (count($plans) == 1)
        {
            $emailData = $this->carQuoteService->buildOnePlansEmailData($lead, reset($plans));
            $emailTemplateId = 490;
        }
        else
        {
            $emailData = $this->carQuoteService->buildMultiplePlansEmailData($lead, reset($plans));
            $emailTemplateId = 493;
        }

        $emailTemplateId = (int) $this->carQuoteService->getAppStorageValueByKey('LMS_INTRO_EMAIL_TEMPLATE_ID'); // template id for LMS intro email

        IntroEmailJob::dispatch(quoteTypeCode::Car, $emailTemplateId, $emailData, 'lms-intro-email');
    }
}
