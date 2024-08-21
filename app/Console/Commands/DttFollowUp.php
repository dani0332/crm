<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\TiersEnum;
use App\Jobs\CarRevivalFollowUpEmailJob;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Models\DttRevival;
use App\Models\Tier;
use App\Services\ApplicationStorageService;
use App\Services\CarQuoteService;
use App\Services\EmailServices\CarEmailService;
use App\Services\SendEmailCustomerService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Sammyjo20\LaravelHaystack\Models\Haystack;

class DttFollowUp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Dtt:followup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This cron will send follow-up email to customer when revival email is not replied OR lead is not assigned';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isDttEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_ENABLED);
        if ($isDttEnabled == false || $isDttEnabled == 0) {
            info('Dtt is not enabled from cms');

            return false;
        }

        $today = Carbon::today();
        $leads = [];
        $twoDaysBefore = Carbon::now()->subDays(2)->toDateString();
        $sevenDaysBefore = Carbon::now()->subDays(7)->toDateString();
        $thirteenDaysBefore = Carbon::now()->subDays(13)->toDateString();
        $twentyDaysBefore = Carbon::now()->subDays(20)->toDateString();
        $twentyEightDaysBefore = Carbon::now()->subDays(28)->toDateString();

        $unreplied = DttRevival::where(function ($q) use ($twoDaysBefore, $sevenDaysBefore, $thirteenDaysBefore, $twentyDaysBefore, $twentyEightDaysBefore) {
            $q->whereDate('created_at', '=', $twoDaysBefore);
            $q->orWhereDate('created_at', '=', $sevenDaysBefore);
            $q->orWhereDate('created_at', '=', $thirteenDaysBefore);
            $q->orWhereDate('created_at', '=', $twentyDaysBefore);
            $q->orWhereDate('created_at', '=', $twentyEightDaysBefore);
        })->where('reply_received', 0)
            ->get();

        $logPrefix = 'carRevivalFollowUpEmailJob -';
        $paymentStatusArray = [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED, PaymentStatusEnum::AUTHORISED];
        $leadSourceArray = [LeadSourceEnum::REVIVAL_PAID];
        foreach ($unreplied as $item) {
            $created_at = $item->created_at;
            $lead = CarQuote::where('uuid', $item->uuid)->first();

            //Follow-up emails will not dispatched if the payment status is either Authorised, Captured, Partial Captured
            //or if the source is Revival Paid or if the lead is assigned to an advisor
            if (! empty($created_at) && ! in_array($lead->payment_status_id, $paymentStatusArray) && ! in_array($lead->source, $leadSourceArray) && empty($lead->advisor_id)) {
                $afterTwoDays = Carbon::parse($created_at)->addDays(2)->startOfDay();
                $afterSevenDays = Carbon::parse($created_at)->addDays(7)->startOfDay();
                $aftertThirteenDays = Carbon::parse($created_at)->addDays(13)->startOfDay();
                $afterTwentyDays = Carbon::parse($created_at)->addDays(20)->startOfDay();
                $afterTwentyeightDays = Carbon::parse($created_at)->addDays(28)->startOfDay();

                try {
                    $listQuotePlans = app(CarQuoteService::class)->getPlans($item->uuid, true, true);
                } catch (\Exception $exception) {
                    info('DTTFolloupListQuotePlansException: '.$exception->getMessage());

                    return false;
                }

                $quotePlansCount = is_countable($listQuotePlans) ? count($listQuotePlans) : 0;

                $tierR = Tier::where('name', TiersEnum::TIER_R)->where('is_active', 1)->first();

                $listQuotePlans = (is_string($listQuotePlans)) ? [] : $listQuotePlans;

                $previousAdvisor = null;
                if (! empty($lead->previous_advisor_id)) {
                    $previousAdvisor = app(UserService::class)->getUserById($lead->previous_advisor_id);
                }
                $emailData = (new CarEmailService(app(SendEmailCustomerService::class)))->buildEmailData($lead, $listQuotePlans, $previousAdvisor, $tierR->id);

                $emailData->customer = (object) ['firstName' => $lead->first_name, 'lastName' => $lead->last_name];
                $dttAdvisor = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::DTT_ADVISOR)->value('value');

                $advisor = explode(',', $dttAdvisor);

                $emailData->uuid = $item->uuid;
                $emailData->advisorName = $advisor[0];
                $emailData->advisorEmail = $advisor[1];
                $emailData->id = $item->id;
                // info($logPrefix.'emailData-'.json_encode($emailData));

                // after two days
                if ($today->eq($afterTwoDays)) {
                    if ($quotePlansCount > 0) {
                        $key = ApplicationStorageEnums::DTT_AFTER_TWO_DAYS_FOLLOWUP_WITH_PLAN;
                    } else {
                        $key = ApplicationStorageEnums::DTT_AFTER_TWO_DAYS_FOLLOWUP_WITHOUT_PLAN;
                    }
                    $emailTemplateId = ApplicationStorage::where('key_name', $key)->value('value');
                    $emailData->templateId = (int) $emailTemplateId;
                    $emailData->subject = 'Reminder: Purchase Your Motor Policy '.$lead->code;
                    $emailData->tag = 'reminder-purchase-your-motor-policy';
                    if ($item->follow_up_email_count == 0) {
                        $leads[] = $emailData;
                    }
                }
                // after seven days
                if ($today->eq($afterSevenDays)) {
                    if ($quotePlansCount > 0) {
                        $key = ApplicationStorageEnums::DTT_AFTER_SEVEN_DAYS_FOLLOWUP_WITH_PLAN;
                    } else {
                        $key = ApplicationStorageEnums::DTT_AFTER_SEVEN_DAYS_FOLLOWUP_WITHOUT_PLAN;
                    }
                    $emailTemplateId = ApplicationStorage::where('key_name', $key)->value('value');
                    $emailData->templateId = (int) $emailTemplateId;
                    $emailData->subject = 'Reminder: Purchase Your Motor Policy '.$lead->code;
                    $emailData->tag = 'reminder-purchase-your-motor-policy';
                    if ($item->follow_up_email_count == 1) {
                        $leads[] = $emailData;
                    }
                }
                // after thirteen days
                if ($today->eq($aftertThirteenDays)) {
                    if ($quotePlansCount > 0) {
                        $key = ApplicationStorageEnums::DTT_AFTER_THIRTEEN_DAYS_FOLLOWUP_WITH_PLAN;
                    } else {
                        $key = ApplicationStorageEnums::DTT_AFTER_THIRTEEN_DAYS_FOLLOWUP_WITHOUT_PLAN;
                    }
                    $emailTemplateId = ApplicationStorage::where('key_name', $key)->value('value');
                    $emailData->templateId = (int) $emailTemplateId;
                    $emailData->subject = 'Friendly Reminder: Secure Your Motor Policy Today '.$lead->code;
                    $emailData->tag = 'friendly-reminder-secure-your-motor-policy';
                    if ($item->follow_up_email_count == 2) {
                        $leads[] = $emailData;
                    }
                }
                // after twenty days
                if ($today->eq($afterTwentyDays)) {
                    if ($quotePlansCount > 0) {
                        $key = ApplicationStorageEnums::DTT_AFTER_TWENTY_DAYS_FOLLOWUP_WITH_PLAN;
                    } else {
                        $key = ApplicationStorageEnums::DTT_AFTER_TWENTY_DAYS_FOLLOWUP_WITHOUT_PLAN;
                    }
                    $emailTemplateId = ApplicationStorage::where('key_name', $key)->value('value');
                    $emailData->templateId = (int) $emailTemplateId;
                    $emailData->subject = 'Gentle Reminder: Secure Your Motor Policy Today '.$lead->code;
                    $emailData->tag = 'gentle-reminder-secure-your-motor-policy';
                    if ($item->follow_up_email_count == 3) {
                        $leads[] = $emailData;
                    }
                }
                // after twentyeight days
                if ($today->eq($afterTwentyeightDays)) {
                    if ($quotePlansCount > 0) {
                        $key = ApplicationStorageEnums::DTT_AFTER_TWENTYEIGHT_DAYS_FOLLOWUP_WITH_PLAN;
                    } else {
                        $key = ApplicationStorageEnums::DTT_AFTER_TWENTYEIGHT_DAYS_FOLLOWUP_WITHOUT_PLAN;
                    }
                    $emailTemplateId = ApplicationStorage::where('key_name', $key)->value('value');
                    $emailData->templateId = (int) $emailTemplateId;
                    $emailData->subject = 'Final Reminder: Secure Your Motor Policy Now '.$lead->code;
                    $emailData->tag = 'final-reminder-secure-your-motor-policy';
                    if ($item->follow_up_email_count == 4) {
                        $leads[] = $emailData;
                    }
                }
            }
        }

        info($logPrefix.'count-'.count($leads).'-leads-'.json_encode(array_column($leads, 'uuid')));

        $jobs = [];
        foreach ($leads as $item) {
            info($logPrefix.'-'.$item->uuid.'-email-'.$item->customerEmail);
            $jobs[] = new CarRevivalFollowUpEmailJob($item);
        }

        if ($jobs != null && count($jobs)) {
            Haystack::build()
                ->addJobs($jobs)

                ->then(function () use ($logPrefix) {
                    info($logPrefix.' all jobs completed successfully');
                })
                ->catch(function () use ($logPrefix) {
                    info($logPrefix.' one of batch is failed.');
                })
                ->finally(function () use ($logPrefix) {
                    info($logPrefix.' everything done');
                })
                ->allowFailures()
                ->withDelay(2)
                ->dispatch();
        } else {
            info($logPrefix.'No lead Found');
        }
    }
}
