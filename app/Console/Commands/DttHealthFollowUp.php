<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Facades\Ken;
use App\Jobs\HealthRevivalFollowUpEmailJob;
use App\Models\ApplicationStorage;
use App\Models\DttRevival;
use App\Models\HealthQuote;
use App\Services\ApplicationStorageService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Sammyjo20\LaravelHaystack\Models\Haystack;

class DttHealthFollowUp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'DttHealthFollowUp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This cron will send follow-up email to customer when revival email is not replied OR lead is not assigned';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDttEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_ENABLED);
        if ($isDttEnabled == false || $isDttEnabled == 0) {
            info('Dtt is not enabled from cms');

            return false;
        }

        $today = Carbon::today();

        $twoDaysBefore = Carbon::now()->subDays(2)->toDateString();
        $fiveDaysBefore = Carbon::now()->subDays(5)->toDateString();
        $eightDaysBefore = Carbon::now()->subDays(8)->toDateString();
        $twelveDaysBefore = Carbon::now()->subDays(12)->toDateString();
        $sixteenDaysBefore = Carbon::now()->subDays(16)->toDateString();
        $twentyDaysBefore = Carbon::now()->subDays(20)->toDateString();

        $fourDaysBefore = Carbon::now()->subDays(4)->toDateString();
        $sixDaysBefore = Carbon::now()->subDays(6)->toDateString();

        $logPrefix = 'healthRevivalFollowUpEmailJob-';

        $leads = [];
        // dd('DTTFollowup date: '.$twoDaysBefore.'-----'.$fiveDaysBefore.'------'.$eightDaysBefore.'------'.$twelveDaysBefore.'------'.$sixteenDaysBefore.'------'.$twentyDaysBefore.'-');
        // dd('DTTFollowup date: ' . $twoDaysBefore . '-----' . $fourDaysBefore . '------' . $sixDaysBefore);

        $unrepliedWithPreviousPlantype = DttRevival::where(function ($q) use ($twoDaysBefore, $fiveDaysBefore, $eightDaysBefore, $twelveDaysBefore, $sixteenDaysBefore, $twentyDaysBefore) {
            $q->whereDate('created_at', '=', $twoDaysBefore);
            $q->orWhereDate('created_at', '=', $fiveDaysBefore);
            $q->orWhereDate('created_at', '=', $eightDaysBefore);
            $q->orWhereDate('created_at', '=', $twelveDaysBefore);
            $q->orWhereDate('created_at', '=', $sixteenDaysBefore);
            $q->orWhereDate('created_at', '=', $twentyDaysBefore);
        })->where('reply_received', 0)->where('quote_type_id', QuoteTypeId::Health)->where('previous_health_plan_type', 1)->get();

        $unrepliedWithoutPreviousPlantype = DttRevival::where(function ($q) use ($twoDaysBefore, $fourDaysBefore, $sixDaysBefore) {
            $q->whereDate('created_at', '=', $twoDaysBefore);
            $q->orWhereDate('created_at', '=', $fourDaysBefore);
            $q->orWhereDate('created_at', '=', $sixDaysBefore);
        })->where('reply_received', 0)->where('quote_type_id', QuoteTypeId::Health)->where('previous_health_plan_type', 0)->get();

        $paymentStatusArray = [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED, PaymentStatusEnum::AUTHORISED];
        $leadStatusArray = [QuoteStatusEnum::ApplicationPending, QuoteStatusEnum::Stale, QuoteStatusEnum::Lost];
        $leadSourceArray = [LeadSourceEnum::REVIVAL_PAID];

        // email payload with  previous health plan type
        foreach ($unrepliedWithPreviousPlantype as $item) {

            if ($today->isWeekend()) {

                info($logPrefix . '-todayIsWeekendCreatedAtUpdated-' . $item->uuid);

                $created_at = Carbon::parse($item->created_at)->addDays(1);
                DttRevival::where('uuid', $item->uuid)->update(['created_at' => $created_at]);

                return false;
            }

            $created_at = $item->created_at;

            $afterTwoDays = Carbon::parse($created_at)->addDays(2)->startOfDay();
            $afterFiveDays = Carbon::parse($created_at)->addDays(5)->startOfDay();
            $afterEightDays = Carbon::parse($created_at)->addDays(8)->startOfDay();
            $afterTwelveDays = Carbon::parse($created_at)->addDays(12)->startOfDay();
            $afterSixteenDays = Carbon::parse($created_at)->addDays(16)->startOfDay();
            $afterTwentyDays = Carbon::parse($created_at)->addDays(20)->startOfDay();

            $healthQuote = HealthQuote::where('uuid', $item->uuid)->first();

            //Follow-up emails will not dispatched if the payment status is either Authorised, Captured, Partial Captured
            //or if the source is Revival Paid or if the lead is assigned to an advisor

            if (! in_array($healthQuote->payment_status_id, $paymentStatusArray) && ! in_array($healthQuote->source, $leadSourceArray) && ! in_array($healthQuote->quote_status_id, $leadStatusArray) && empty($healthQuote->advisor_id)) {

                $customerName = $healthQuote->first_name . ' ' . $healthQuote->last_name;
                $response = Ken::request('/get-health-quote-plans-order-priority', 'post', [
                    'quoteUID' => $healthQuote->uuid,
                ]);

                if (empty($response['quote']['plans'])) {
                    info($logPrefix . 'noPlansReturned-' . $healthQuote->uuid);

                    continue;
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
                    $planObj->total = $plan['premium'];
                    $planObj->buynowURL = $plan['planLink'];

                    $lowestRate = collect($plan['ratesPerCopay'])->sortBy('discountPremium')->first();

                    $coPaymentsCollection = collect($plan['coPayments']);
                    $filteredSelectedCopay = $coPaymentsCollection->where('id', $lowestRate['healthPlanCoPaymentId'])->first();
                    $planObj->planBenefit = $this->getBenefitsDetails($plan['benefits'], $filteredSelectedCopay);
                    $plansArray[] = $planObj;
                }

                $emailData = new \stdClass;

                $key = ApplicationStorageEnums::DTT_HEALTH_INITIAL_AND_FOLLOWUP_TEMPLATE;

                $emailTemplateId = ApplicationStorage::where('key_name', $key)->value('value');

                $emailData->quotePlanLink = config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL') . $healthQuote->uuid;

                $emailData->plans = $plansArray;

                $emailData->subject = $customerName . "'s" . ' Health Insurance with Alfred ' . $healthQuote->code;
                $emailData->customerName = $customerName;
                $emailData->customerEmail = $healthQuote->email;
                $emailData->templateId = (int) $emailTemplateId;
                $emailData->uuid = $healthQuote->uuid;
                $emailData->id = $healthQuote->id;
                $emailData->fromEmail = ApplicationStorageEnums::DTT_HEALTH_FOLLOWUP_FROM_EMAIL;

                $emailData->requestAdvisorLink = config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL') . $healthQuote->uuid . '/?assignAdvisor=true';
                // after two days
                if ($today->eq($afterTwoDays)) {
                    $emailData->subject = 'Urgent: Renew your health insurance today! ' . $healthQuote->code;
                    $emailData->tag = 'health-revival-followup1-email';
                    $emailData->templateType = 'revivalHealthFU1';
                    if ($item->follow_up_email_count == 0) {
                        $leads[] = $emailData;
                    }
                }
                // after five days
                if ($today->eq($afterFiveDays)) {
                    $emailData->subject = 'Unlock your tailored health insurance quotes and renew now! ' . $healthQuote->code;
                    $emailData->tag = 'health-revival-followup2-email';
                    $emailData->templateType = 'revivalHealthFU2';
                    if ($item->follow_up_email_count == 1) {
                        $leads[] = $emailData;
                    }
                }  // after eight days
                if ($today->eq($afterEightDays)) {
                    $emailData->subject = 'Renew the coverage you need to protect your health today! ' . $healthQuote->code;
                    $emailData->tag = 'health-revival-followup3-email';
                    $emailData->templateType = 'revivalHealthFU3';
                    if ($item->follow_up_email_count == 2) {
                        $leads[] = $emailData;
                    }
                } // after twelve days
                if ($today->eq($afterTwelveDays)) {
                    $emailData->subject = 'Your health insurance renewal options await! ' . $healthQuote->code;
                    $emailData->tag = 'health-revival-followup4-email';
                    $emailData->templateType = 'revivalHealthFU4';
                    if ($item->follow_up_email_count == 3) {
                        $leads[] = $emailData;
                    }
                } // after sixteen days
                if ($today->eq($afterSixteenDays)) {
                    $emailData->subject = 'Explore your health coverage renewal options now! ' . $healthQuote->code;
                    $emailData->tag = 'health-revival-followup5-email';
                    $emailData->templateType = 'revivalHealthFU5';
                    if ($item->follow_up_email_count == 4) {
                        $leads[] = $emailData;
                    }
                } // after twenty days
                if ($today->eq($afterTwentyDays)) {
                    $emailData->subject = 'Your next step for seamless health coverage renewal awaits! ' . $healthQuote->code;
                    $emailData->tag = 'health-revival-followup6-email';
                    $emailData->templateType = 'revivalHealthFU6';
                    if ($item->follow_up_email_count == 5) {
                        $leads[] = $emailData;
                    }
                }
            }
        }

        // email payload without previous health plan type
        foreach ($unrepliedWithoutPreviousPlantype as $item) {

            $emailData = new \stdClass;
            $created_at = $item->created_at;

            if ($today->isWeekend()) {

                info($logPrefix . '-todayIsWeekendCreatedAtUpdated-' . $item->uuid);

                $created_at = Carbon::parse($item->created_at)->addDays(1);
                DttRevival::where('uuid', $item->uuid)->update(['created_at' => $created_at]);

                return false;
            }
            $afterTwoDays = Carbon::parse($created_at)->addDays(2)->startOfDay();
            $afterFourDays = Carbon::parse($created_at)->addDays(4)->startOfDay();
            $afterSixDays = Carbon::parse($created_at)->addDays(6)->startOfDay();

            $lead = HealthQuote::where('uuid', $item->uuid)->first();

            //Follow-up emails will not dispatched if the payment status is either Authorised, Captured, Partial Captured
            //or if the source is Revival Paid or if the lead is assigned to an advisor
            if (! in_array($lead->payment_status_id, $paymentStatusArray) && ! in_array($lead->source, $leadSourceArray) && ! in_array($lead->quote_status_id, $leadStatusArray) && empty($lead->advisor_id)) {
                $customerName = $lead->first_name . ' ' . $lead->last_name;
                $emailData->customerName = $customerName;
                $emailData->customerEmail = $lead->email;
                $emailData->fromEmail = ApplicationStorageEnums::DTT_HEALTH_FOLLOWUP_FROM_EMAIL;

                $emailData->requestAdvisorLink = config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL') . $lead->uuid . '/?assignAdvisor=true';

                $response = Ken::request('/get-health-cheapest-plans', 'post', [
                    'quoteUID' => $lead->uuid,
                    'isPlanTypes' => true,
                ]);

                if (empty($response['planTypes'])) {
                    info($logPrefix . 'noPlansTypesReturned-' . $lead->uuid);

                    continue;
                }

                // after two days
                if ($today->eq($afterTwoDays)) {
                    $key = ApplicationStorageEnums::DTT_HEALTH_FOLLOWUP_AFTER_TWO_DAYS_WITHOUT_HEALTH_TEAM;
                    $emailData->uuid = $item->uuid;
                    $emailData->id = $item->id;
                    $emailTemplateId = ApplicationStorage::where('key_name', $key)->value('value');
                    $emailData->templateId = (int) $emailTemplateId;
                    $emailData->subject = 'Seize the opportunity to renew your health insurance today ' . $lead->code;
                    $emailData->tag = 'first-follow-up-email-' . $lead->code;

                    $emailData->planTypes = $response['planTypes'];
                    if ($item->follow_up_email_count == 0) {
                        $leads[] = $emailData;
                    }
                }
                // after four days
                if ($today->eq($afterFourDays)) {
                    $key = ApplicationStorageEnums::DTT_HEALTH_FOLLOWUP_AFTER_FOUR_DAYS_WITHOUT_HEALTH_TEAM;
                    $emailData->uuid = $item->uuid;
                    $emailData->id = $item->id;
                    $emailTemplateId = ApplicationStorage::where('key_name', $key)->value('value');
                    $emailData->templateId = (int) $emailTemplateId;
                    $emailData->subject = 'Act now: Renew your health insurance policy ' . $lead->code;
                    $emailData->tag = 'first-follow-up-email-' . $lead->code;

                    $emailData->planTypes = $response['planTypes'];
                    if ($item->follow_up_email_count == 1) {
                        $leads[] = $emailData;
                    }
                } // after six days
                if ($today->eq($afterSixDays)) {
                    $key = ApplicationStorageEnums::DTT_HEALTH_FOLLOWUP_AFTER_SIX_DAYS_WITHOUT_HEALTH_TEAM;
                    $emailData->uuid = $item->uuid;
                    $emailData->id = $item->id;
                    $emailTemplateId = ApplicationStorage::where('key_name', $key)->value('value');
                    $emailData->templateId = (int) $emailTemplateId;
                    $emailData->subject = 'Last chance: Renew your health insurance today ' . $lead->code;
                    $emailData->tag = 'first-follow-up-email-' . $lead->code;

                    $emailData->planTypes = $response['planTypes'];
                    if ($item->follow_up_email_count == 2) {
                        $leads[] = $emailData;
                    }
                }
            }
        }

        info($logPrefix . 'count-' . count($leads) . '-leads-' . json_encode(array_column($leads, 'uuid')));

        $jobs = [];
        foreach ($leads as $item) {
            info($logPrefix . '-' . $item->uuid . '-email-' . $item->customerEmail);
            $jobs[] = new HealthRevivalFollowUpEmailJob($item);
        }

        if ($jobs != null && count($jobs)) {
            Haystack::build()
                ->addJobs($jobs)

                ->then(function () use ($logPrefix) {
                    info($logPrefix . ' all jobs completed successfully');
                })
                ->catch(function () use ($logPrefix) {
                    info($logPrefix . ' one of batch is failed.');
                })
                ->finally(function () use ($logPrefix) {
                    info($logPrefix . ' everything done');
                })
                ->allowFailures()
                ->withDelay(30)
                ->dispatch();
        } else {
            info($logPrefix . 'No lead Found');
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
