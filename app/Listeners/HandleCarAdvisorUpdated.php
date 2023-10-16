<?php

namespace App\Listeners;

use App\Enums\CarPlanType;
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
        info('inside build sms');
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

        $plans = $this->executePlansSelectionLogic($plans);

        info('plans', $plans);

        // Determine the email template ID
        $emailTemplateId = $this->getEmailTemplateId($lead, $plans, $tierR);

        // Build email data
        $emailData = $this->buildEmailData($lead, $plans, $previousAdvisor, $tierR->id);

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

    public function executePlansSelectionLogic(array $plans): array
    {
        // Sort plans from lowest to highest by discount premium
        usort($plans, function ($a, $b) {
            return $a->discountPremium <=> $b->discountPremium;
        });

        // Check if there are any 'Comp' plans
        $compPlans = array_filter($plans, function ($plan) {
            // Check if the 'repairType' and 'isRatingAvailable' properties exist and meet the conditions.
            return property_exists($plan, 'repairType') &&
                   property_exists($plan, 'isRatingAvailable') &&
                   ($plan->repairType === CarPlanType::COMP || $plan->repairType === CarPlanType::AGENCY) &&
                   $plan->isRatingAvailable === true;
        });

        if (count($compPlans) > 0) {
            // If 'Comp' plans exist, return the top 6 'Comp' plans
            $top6Plans = array_slice($compPlans, 0, 6);
        } else {
            // If there are no 'Comp' plans, return the top 6 plans
            $filteredPlans = array_filter($plans, function ($plan) {
                return $plan->repairType == CarPlanType::TPL && $plan->isRatingAvailable == true;
            });

            $top6Plans = array_slice($filteredPlans, 0, 6);
        }

        // return $top6Plans if $top6Plans is not empty otherwise return $plans
        return ! empty($top6Plans) ? $top6Plans : [];
    }
}
