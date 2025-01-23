<?php

namespace App\Jobs\Revival;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\TiersEnum;
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
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

class CarRevivalFollowUpEmailJob implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, Stackable;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 300;
    private $dttRevival = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($dttRevival)
    {
        $this->dttRevival = $dttRevival;
        $this->onQueue('renewals');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $isDttEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_ENABLED);
        if ($isDttEnabled == false || $isDttEnabled == 0) {
            info('Dtt is not enabled from cms');

            return false;
        }

        $today = Carbon::today();

        $paymentStatusArray = [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED, PaymentStatusEnum::AUTHORISED];
        $leadSourceArray = [LeadSourceEnum::REVIVAL_PAID];
        $leadStatusArray = [QuoteStatusEnum::Duplicate, QuoteStatusEnum::Fake];

        $created_at = $this->dttRevival->created_at;
        $lead = CarQuote::where('uuid', $this->dttRevival->uuid)->first();

        // Follow-up emails will not dispatched if the payment status is either Authorised, Captured, Partial Captured
        // or if the source is Revival Paid or if the lead is assigned to an advisor
        if ($lead && ! empty($created_at) && ! in_array($lead->quote_status_id, $leadStatusArray) && ! in_array($lead->payment_status_id, $paymentStatusArray) && ! in_array($lead->source, $leadSourceArray) && empty($lead->advisor_id)) {
            $afterTwoDays = Carbon::parse($created_at)->addDays(2)->startOfDay();
            $afterSevenDays = Carbon::parse($created_at)->addDays(7)->startOfDay();
            $aftertThirteenDays = Carbon::parse($created_at)->addDays(13)->startOfDay();
            $afterTwentyDays = Carbon::parse($created_at)->addDays(20)->startOfDay();
            $afterTwentyeightDays = Carbon::parse($created_at)->addDays(28)->startOfDay();

            try {
                $listQuotePlans = app(CarQuoteService::class)->getPlans($this->dttRevival->uuid, true, true);
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

            $emailData->uuid = $this->dttRevival->uuid;
            $emailData->advisorName = $advisor[0];
            $emailData->advisorEmail = $advisor[1];
            $emailData->id = $this->dttRevival->id;
            $emailData->lob = QuoteTypes::CAR->id();

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
                if ($this->dttRevival->follow_up_email_count == 0) {
                    $this->sendFollowUpEmail($emailData);
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
                if ($this->dttRevival->follow_up_email_count == 1) {
                    $this->sendFollowUpEmail($emailData);
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
                if ($this->dttRevival->follow_up_email_count == 2) {
                    $this->sendFollowUpEmail($emailData);
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
                if ($this->dttRevival->follow_up_email_count == 3) {
                    $this->sendFollowUpEmail($emailData);
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
                if ($this->dttRevival->follow_up_email_count == 4) {
                    $this->sendFollowUpEmail($emailData);
                }
            }
        }

    }

    private function sendFollowUpEmail($emailData)
    {
        $response = app(SendEmailCustomerService::class)->sendDttEmail($emailData);
        if ($response == 201) {
            DttRevival::where('id', $this->dttRevival->id)->increment('follow_up_email_count');
            info('CarRevivalFollowUpEmailJob email is sent '.$this->dttRevival->uuid.' - '.$emailData->customerEmail);
        } else {
            info('CarRevivalFollowUpEmailJob email not sent '.$this->dttRevival->uuid.' - '.$emailData->customerEmail);
        }
    }
}
