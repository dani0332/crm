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
use App\Services\CarEmailService;
use App\Services\CarQuoteService;
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

        try {

            $isDttEnabled = app(ApplicationStorageService::class)->getIsActiveByKey(ApplicationStorageEnums::DTT_ENABLED);
            if (! $isDttEnabled) {
                info('Dtt is not enabled from cms');

                return false;
            }

            $today = Carbon::today();
            $leads = [];
            $unreplied = DttRevival::where([
                ['reply_received', 0],
                ['is_assigned', 0],
            ])->get();

            $logPrefix = 'carRevivalFollowUpEmailJob-';
            $paymentStatusArray = [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED, PaymentStatusEnum::AUTHORISED];
            $leadSourceArray = [LeadSourceEnum::REVIVAL_PAID];
            foreach ($unreplied as $item) {
                $created_at = $item->created_at;
                $lead = CarQuote::where('uuid', $item->uuid)->first();
                if (! empty($created_at) && ! in_array($lead->payment_status_id, $paymentStatusArray) && ! in_array($lead->source, $leadSourceArray)) {

                    $afterTwoDays = Carbon::parse($created_at)->addDays(2)->startOfDay();
                    $afterSevenDays = Carbon::parse($created_at)->addDays(7)->startOfDay();
                    $aftertThirteenDays = Carbon::parse($created_at)->addDays(13)->startOfDay();
                    $afterTwentyDays = Carbon::parse($created_at)->addDays(20)->startOfDay();
                    $afterTwentyeightDays = Carbon::parse($created_at)->addDays(28)->startOfDay();

                    $listQuotePlans = app(CarQuoteService::class)->getPlans($item->uuid, true, true);

                    $quotePlansCount = is_countable($listQuotePlans) ? count($listQuotePlans) : 0;

                    $tierR = Tier::where('name', TiersEnum::TIER_R)->where('is_active', 1)->first();

                    $listQuotePlans = (is_string($listQuotePlans)) ? [] : $listQuotePlans;

                    $previousAdvisor = null;
                    if (! empty($lead->previous_advisor_id)) {
                        $previousAdvisor = app(userService::class)->getUserById($lead->previous_advisor_id);
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
                        $leads[] = $emailData;
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
                        $leads[] = $emailData;
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
                        $leads[] = $emailData;
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
                        $leads[] = $emailData;
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
                        $leads[] = $emailData;
                    }
                }
            }

            info($logPrefix.'count -'.count($leads));

            $jobs = [];
            foreach ($leads as $item) {

                info($logPrefix.'-'.$item->uuid.'-email-'.$item->email);
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
        } catch (\Exception $exception) {
            info('DTT folloup Exception : '.$exception->getMessage());
        }
    }
}
