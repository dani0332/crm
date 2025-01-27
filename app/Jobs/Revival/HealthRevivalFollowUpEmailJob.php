<?php

namespace App\Jobs\Revival;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Facades\Ken;
use App\Models\ApplicationStorage;
use App\Models\DttRevival;
use App\Models\HealthQuote;
use App\Services\ApplicationStorageService;
use App\Services\SendEmailCustomerService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

class HealthRevivalFollowUpEmailJob implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, Stackable;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = 300;
    private $uuid = null;
    private $type = null;
    private $logPrefix = 'DTTHealth - HealthRevivalFollowUpEmailJob - ';

    /**
     * Create a new job instance.
     */
    public function __construct($uuid, $type)
    {
        $this->uuid = $uuid;
        $this->type = $type;
        $this->onQueue('renewals');
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        if ($this->uuid == null || $this->type == null) {
            info('HealthRevivalFollowUpEmailJob - UUID or Type Null. UUID: '.$this->uuid.' - Type: '.$this->type);

            return false;
        }

        $isDttEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_HEALTH_ENABLED);
        if ($isDttEnabled == false || $isDttEnabled == 0) {
            info('DTT_HEALTH is not enabled from cms');

            return false;
        }
        if ($this->type == 'unrepliedWithPreviousPlantype') {
            $this->unrepliedWithPreviousPlantype();
        }
        if ($this->type == 'unrepliedWithoutPreviousPlantype') {
            $this->unrepliedWithoutPreviousPlantype();
        }
    }

    private function unrepliedWithPreviousPlantype()
    {
        $paymentStatusArray = [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED, PaymentStatusEnum::AUTHORISED];
        $leadStatusArray = [QuoteStatusEnum::ApplicationPending, QuoteStatusEnum::Stale, QuoteStatusEnum::Lost];
        $leadSourceArray = [LeadSourceEnum::REVIVAL_PAID];

        $item = DttRevival::where('uuid', $this->uuid)->where('is_active', 1)->first();
        if (! $item) {
            info($this->logPrefix.'Revival Follow Up Not Active - UUID: '.$this->uuid);

            return false;
        }
        $created_at = $item->created_at;

        $today = Carbon::today();

        $afterTwoDays = Carbon::parse($created_at)->addDays(2)->startOfDay();
        $afterFiveDays = Carbon::parse($created_at)->addDays(5)->startOfDay();
        $afterEightDays = Carbon::parse($created_at)->addDays(8)->startOfDay();
        $afterTwelveDays = Carbon::parse($created_at)->addDays(12)->startOfDay();
        $afterSixteenDays = Carbon::parse($created_at)->addDays(16)->startOfDay();
        $afterTwentyDays = Carbon::parse($created_at)->addDays(20)->startOfDay();

        $healthQuote = HealthQuote::where('uuid', $item->uuid)->first();

        // Follow-up emails will not dispatched if the payment status is either Authorised, Captured, Partial Captured
        // or if the source is Revival Paid or if the lead is assigned to an advisor

        if (! in_array($healthQuote->payment_status_id, $paymentStatusArray) && ! in_array($healthQuote->source, $leadSourceArray) && ! in_array($healthQuote->quote_status_id, $leadStatusArray) && empty($healthQuote->advisor_id)) {

            $customerName = $healthQuote->first_name.' '.$healthQuote->last_name;
            $response = Ken::renewalRequest('/get-health-quote-plans-order-priority', 'post', [
                'quoteUID' => $healthQuote->uuid,
                'isModified' => true,
            ]);

            if (empty($response['quote']['plans'])) {
                info($this->logPrefix.'noPlansReturned - '.$healthQuote->uuid);

                return false;
            }
            $plansArray = [];
            foreach ($response['quote']['plans'] as $plan) {
                $planObj = new \stdClass;
                $planObj->id = $plan['id'];
                $planObj->name = $plan['name'];
                $planObj->providerName = $plan['providerName'];
                $planObj->eligibilityName = $plan['eligibilityName'];
                $planObj->planCode = $plan['planCode'];
                $planObj->providerCode = $plan['providerCode'];
                $planObj->total = $plan['total'];
                $planObj->buynowURL = $plan['buynowURL'];
                $planObj->planBenefit = $plan['planBenefit'];

                $plansArray[] = $planObj;
            }

            $emailData = new \stdClass;

            $key = ApplicationStorageEnums::DTT_HEALTH_INITIAL_AND_FOLLOWUP_TEMPLATE;

            $emailTemplateId = ApplicationStorage::where('key_name', $key)->value('value');

            $emailData->quotePlanLink = config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$healthQuote->uuid;

            $emailData->plans = $plansArray;

            $emailData->subject = $customerName."'s".' Health Insurance with Alfred '.$healthQuote->code;
            $emailData->customerName = $customerName;
            $emailData->customerEmail = $healthQuote->email;
            $emailData->templateId = (int) $emailTemplateId;
            $emailData->uuid = $healthQuote->uuid;
            $emailData->id = $healthQuote->id;
            $emailData->quotePlanLink = $response['quote']['quotePlanLink'];
            $emailData->requestAdvisorLink = $response['quote']['requestAdvisorLink'];

            $key = ApplicationStorageEnums::DTT_HEALTH_FOLLOWUP_FROM_EMAIL;

            $emailData->fromEmail = ApplicationStorage::where('key_name', $key)->value('value');
            $emailData->lob = QuoteTypes::HEALTH->id();
            // after two days
            if ($today->eq($afterTwoDays)) {
                $emailData->subject = 'Urgent: Renew your health insurance today! '.$healthQuote->code;
                $emailData->tag = 'health-revival-followup1-email';
                $emailData->templateType = 'revivalHealthFU1';
                if ($item->follow_up_email_count == 0) {
                    $this->sendDTTFollowUpEmail($emailData);
                }
            }
            // after five days
            if ($today->eq($afterFiveDays)) {
                $emailData->subject = 'Unlock your tailored health insurance quotes and renew now! '.$healthQuote->code;
                $emailData->tag = 'health-revival-followup2-email';
                $emailData->templateType = 'revivalHealthFU2';
                if ($item->follow_up_email_count == 1) {
                    $this->sendDTTFollowUpEmail($emailData);
                }
            }  // after eight days
            if ($today->eq($afterEightDays)) {
                $emailData->subject = 'Renew the coverage you need to protect your health today! '.$healthQuote->code;
                $emailData->tag = 'health-revival-followup3-email';
                $emailData->templateType = 'revivalHealthFU3';
                if ($item->follow_up_email_count == 2) {
                    $this->sendDTTFollowUpEmail($emailData);
                }
            } // after twelve days
            if ($today->eq($afterTwelveDays)) {
                $emailData->subject = 'Your health insurance renewal options await! '.$healthQuote->code;
                $emailData->tag = 'health-revival-followup4-email';
                $emailData->templateType = 'revivalHealthFU4';
                if ($item->follow_up_email_count == 3) {
                    $this->sendDTTFollowUpEmail($emailData);
                }
            } // after sixteen days
            if ($today->eq($afterSixteenDays)) {
                $emailData->subject = 'Explore your health coverage renewal options now! '.$healthQuote->code;
                $emailData->tag = 'health-revival-followup5-email';
                $emailData->templateType = 'revivalHealthFU5';
                if ($item->follow_up_email_count == 4) {
                    $this->sendDTTFollowUpEmail($emailData);
                }
            } // after twenty days
            if ($today->eq($afterTwentyDays)) {
                $emailData->subject = 'Your next step for seamless health coverage renewal awaits! '.$healthQuote->code;
                $emailData->tag = 'health-revival-followup6-email';
                $emailData->templateType = 'revivalHealthFU6';
                if ($item->follow_up_email_count == 5) {
                    $this->sendDTTFollowUpEmail($emailData);
                }
            }
        } else {
            info($this->logPrefix.'Disabling DTT Revival Follow Up - UUID: '.$item->uuid);
            DttRevival::where('uuid', $item->uuid)->update(['is_active' => false]);
        }
    }

    private function unrepliedWithoutPreviousPlantype()
    {
        $paymentStatusArray = [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED, PaymentStatusEnum::AUTHORISED];
        $leadStatusArray = [QuoteStatusEnum::ApplicationPending, QuoteStatusEnum::Stale, QuoteStatusEnum::Lost];
        $leadSourceArray = [LeadSourceEnum::REVIVAL_PAID];

        $item = DttRevival::where('uuid', $this->uuid)->first();
        if (! $item) {
            info($this->logPrefix.'Revival Follow Up Not Active - UUID: '.$item->uuid);

            return false;
        }
        $created_at = $item->created_at;

        $today = Carbon::today();
        $afterTwoDays = Carbon::parse($created_at)->addDays(2)->startOfDay();
        $afterFourDays = Carbon::parse($created_at)->addDays(4)->startOfDay();
        $afterSixDays = Carbon::parse($created_at)->addDays(6)->startOfDay();

        $lead = HealthQuote::where('uuid', $item->uuid)->first();
        $emailData = new \stdClass;
        $created_at = $item->created_at;

        // Follow-up emails will not dispatched if the payment status is either Authorised, Captured, Partial Captured
        // or if the source is Revival Paid or if the lead is assigned to an advisor
        if (! in_array($lead->payment_status_id, $paymentStatusArray) && ! in_array($lead->source, $leadSourceArray) && ! in_array($lead->quote_status_id, $leadStatusArray) && empty($lead->advisor_id)) {
            $customerName = $lead->first_name.' '.$lead->last_name;
            $emailData->customerName = $customerName;
            $emailData->customerEmail = $lead->email;

            $key = ApplicationStorageEnums::DTT_HEALTH_FOLLOWUP_FROM_EMAIL;

            $emailData->fromEmail = ApplicationStorage::where('key_name', $key)->value('value');
            $emailData->lob = QuoteTypes::HEALTH->id();
            $response = Ken::renewalRequest('/get-health-cheapest-plans', 'post', [
                'quoteUID' => $lead->uuid,
                'isPlanTypes' => true,
            ]);

            if (empty($response['planTypes'])) {
                info($this->logPrefix.'noPlansReturned - '.$lead->uuid);

                return false;
            }

            // after two days
            if ($today->eq($afterTwoDays)) {
                $key = ApplicationStorageEnums::DTT_HEALTH_FOLLOWUP_AFTER_TWO_DAYS_WITHOUT_HEALTH_TEAM;
                $emailData->uuid = $item->uuid;
                $emailData->id = $item->id;
                $emailTemplateId = ApplicationStorage::where('key_name', $key)->value('value');
                $emailData->templateId = (int) $emailTemplateId;
                $emailData->subject = 'Seize the opportunity to renew your health insurance today '.$lead->code;
                $emailData->tag = 'first-follow-up-email-'.$lead->code;

                $emailData->planTypes = $response['planTypes'];
                if ($item->follow_up_email_count == 0) {
                    $this->sendDTTFollowUpEmail($emailData);
                }
            }
            // after four days
            if ($today->eq($afterFourDays)) {
                $key = ApplicationStorageEnums::DTT_HEALTH_FOLLOWUP_AFTER_FOUR_DAYS_WITHOUT_HEALTH_TEAM;
                $emailData->uuid = $item->uuid;
                $emailData->id = $item->id;
                $emailTemplateId = ApplicationStorage::where('key_name', $key)->value('value');
                $emailData->templateId = (int) $emailTemplateId;
                $emailData->subject = 'Act now: Renew your health insurance policy '.$lead->code;
                $emailData->tag = 'first-follow-up-email-'.$lead->code;

                $emailData->planTypes = $response['planTypes'];
                if ($item->follow_up_email_count == 1) {
                    $this->sendDTTFollowUpEmail($emailData);
                }
            } // after six days
            if ($today->eq($afterSixDays)) {
                $key = ApplicationStorageEnums::DTT_HEALTH_FOLLOWUP_AFTER_SIX_DAYS_WITHOUT_HEALTH_TEAM;
                $emailData->uuid = $item->uuid;
                $emailData->id = $item->id;
                $emailTemplateId = ApplicationStorage::where('key_name', $key)->value('value');
                $emailData->templateId = (int) $emailTemplateId;
                $emailData->subject = 'Last chance: Renew your health insurance today '.$lead->code;
                $emailData->tag = 'first-follow-up-email-'.$lead->code;

                $emailData->planTypes = $response['planTypes'];
                if ($item->follow_up_email_count == 2) {
                    $this->sendDTTFollowUpEmail($emailData);
                }
            }
        } else {
            info($this->logPrefix.'Disabling DTT Revival Follow Up - UUID: '.$item->uuid);
            DttRevival::where('uuid', $item->uuid)->update(['is_active' => false]);
        }
    }

    private function sendDTTFollowUpEmail($emailData)
    {
        $response = app(SendEmailCustomerService::class)->sendDttEmail($emailData);
        if ($response == 201) {

            $lead = HealthQuote::where('uuid', $emailData->uuid)->first();
            if ($lead->quote_status_id != QuoteStatusEnum::FollowedUp) {
                $lead->update(['quote_status_id' => QuoteStatusEnum::FollowedUp]);
            }
            $revivalRecord = DttRevival::where('uuid', $emailData->uuid)->first();
            $revivalRecord->increment('follow_up_email_count');
            if ($revivalRecord && $revivalRecord->follow_up_email_count == 6 && $revivalRecord->previous_health_plan_type) {
                $lead->update(['quote_status_id' => QuoteStatusEnum::Stale]);
            }
            if ($revivalRecord && $revivalRecord->follow_up_email_count == 3 && $revivalRecord->previous_health_plan_type == false) {
                $lead->update(['quote_status_id' => QuoteStatusEnum::Stale]);
            }

            info($this->logPrefix.'Email Sent - UUID - '.$emailData->uuid.' Follow Up Count: '.$revivalRecord->follow_up_email_count);
        } else {
            info($this->logPrefix.'Email NOT Sent - UUID - '.$emailData->uuid);
        }
    }

    private function getBenefitsDetails($benefitList, $filteredSelectedCopay)
    {
        $benefitsTypes = [];
        $getBenefitCode = ['annualLimit', 'regionsCovered'];

        foreach ($benefitList as $key => $covers) {

            if ($key == 'outpatient') {
                foreach ($covers as $cover) {
                    if ($cover['code'] === 'medicine') {
                        $benefitsTypes[$cover['code']] = [
                            'text' => $cover['value'] ?? '',
                        ];
                    }
                }
            } else {
                foreach ($covers as $cover) {
                    if (in_array($cover['code'], $getBenefitCode)) {
                        $benefitsTypes[$cover['code']] = [
                            'text' => $cover['value'] ?? '',
                        ];
                    }
                    if ($cover['code'] == 'outpatientConsultation') {
                        $benefitsTypes[$cover['code']] = [
                            'text' => $filteredSelectedCopay['text'] ?? '',
                        ];
                    }
                }
            }
        }

        return $benefitsTypes;
    }
}
