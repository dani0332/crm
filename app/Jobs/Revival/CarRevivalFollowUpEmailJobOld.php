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
use App\Services\Logger\LoggerService;
use App\Services\SendEmailCustomerService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class CarRevivalFollowUpEmailJobOld implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    private const LEGACY_FOLLOW_UP_CUTOFF_DATE = '2026-04-29';

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 300;
    private $dttRevival = null;
    private $dttRevivalId = null;
    private ?string $followUpAnchorDate = null;

    /**
     * Create a new job instance.
     *
     * @param  mixed  $dttRevivalId
     */
    public function __construct($dttRevivalId, ?string $followUpAnchorDate = null)
    {
        $this->dttRevivalId = $dttRevivalId;
        $this->followUpAnchorDate = $followUpAnchorDate;
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
            LoggerService::info('Dtt is not enabled from cms');

            return false;
        }

        // Fetch the DttRevival model to avoid serialization issues
        $this->dttRevival = DttRevival::find($this->dttRevivalId);

        // If the record was deleted between job creation and execution, exit gracefully
        if ($this->dttRevival === null) {
            LoggerService::info('DttRevival record not found (ID: '.$this->dttRevivalId.'). Record may have been deleted.');

            return false;
        }

        if (Carbon::parse($this->dttRevival->created_at)->isAfter(Carbon::parse(self::LEGACY_FOLLOW_UP_CUTOFF_DATE)->endOfDay())) {
            LoggerService::info('Skipping non-legacy dtt revival follow-up (created after cutoff date)', [
                'dtt_revival_id' => $this->dttRevivalId,
                'created_at' => $this->dttRevival->created_at,
                'cutoff_date' => self::LEGACY_FOLLOW_UP_CUTOFF_DATE,
            ]);

            return false;
        }

        $today = $this->followUpAnchorDate !== null
            ? Carbon::parse($this->followUpAnchorDate)->startOfDay()
            : Carbon::today();

        $paymentStatusArray = [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED, PaymentStatusEnum::AUTHORISED];
        $leadSourceArray = [LeadSourceEnum::REVIVAL_PAID];
        $leadStatusArray = [QuoteStatusEnum::Duplicate, QuoteStatusEnum::Fake];

        $created_at = $this->dttRevival->created_at;
        $lead = CarQuote::where('uuid', $this->dttRevival->uuid)->first();

        $skipReason = null;
        if (! $lead) {
            $skipReason = 'lead_not_found';
        } elseif (empty($created_at)) {
            $skipReason = 'dtt_revival_created_at_empty';
        } elseif (in_array($lead->quote_status_id, $leadStatusArray)) {
            $skipReason = 'quote_status_duplicate_or_fake';
        } elseif (in_array($lead->payment_status_id, $paymentStatusArray)) {
            $skipReason = 'payment_authorised_or_captured';
        } elseif (in_array($lead->source, $leadSourceArray)) {
            $skipReason = 'source_revival_paid';
        } elseif (! empty($lead->advisor_id)) {
            $skipReason = 'advisor_already_assigned';
        }

        if ($skipReason !== null) {
            LoggerService::info('Skipping dtt follow-up due to eligibility filters', [
                'dtt_revival_id' => $this->dttRevival->id,
                'child_quote_uuid' => $this->dttRevival->uuid,
                'skip_reason' => $skipReason,
                'quote_status_id' => $lead?->quote_status_id,
                'payment_status_id' => $lead?->payment_status_id,
                'source' => $lead?->source,
                'advisor_id' => $lead?->advisor_id,
            ]);

            return false;
        }

        $dueDateMatched = false;
        $attemptedSend = false;
        $expectedFollowUpCount = null;

        if ($lead && ! empty($created_at)) {
            $afterTwoDays = Carbon::parse($created_at)->addDays(2)->startOfDay();
            $afterSevenDays = Carbon::parse($created_at)->addDays(7)->startOfDay();
            $aftertThirteenDays = Carbon::parse($created_at)->addDays(13)->startOfDay();
            $afterTwentyDays = Carbon::parse($created_at)->addDays(20)->startOfDay();
            $afterTwentyeightDays = Carbon::parse($created_at)->addDays(28)->startOfDay();

            try {
                $listQuotePlans = app(CarQuoteService::class)->getPlans($this->dttRevival->uuid, true, true);
            } catch (\Exception $exception) {
                LoggerService::info('DTTFolloupListQuotePlansException: '.$exception->getMessage());

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
                $dueDateMatched = true;
                $expectedFollowUpCount = 0;
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
                    $attemptedSend = true;
                    $this->sendFollowUpEmail($emailData);
                }
            }
            // after seven days
            if ($today->eq($afterSevenDays)) {
                $dueDateMatched = true;
                $expectedFollowUpCount = 1;
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
                    $attemptedSend = true;
                    $this->sendFollowUpEmail($emailData);
                }
            }
            // after thirteen days
            if ($today->eq($aftertThirteenDays)) {
                $dueDateMatched = true;
                $expectedFollowUpCount = 2;
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
                    $attemptedSend = true;
                    $this->sendFollowUpEmail($emailData);
                }
            }
            // after twenty days
            if ($today->eq($afterTwentyDays)) {
                $dueDateMatched = true;
                $expectedFollowUpCount = 3;
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
                    $attemptedSend = true;
                    $this->sendFollowUpEmail($emailData);
                }
            }
            // after twentyeight days
            if ($today->eq($afterTwentyeightDays)) {
                $dueDateMatched = true;
                $expectedFollowUpCount = 4;
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
                    $attemptedSend = true;
                    $this->sendFollowUpEmail($emailData);
                }
            }

            if (! $dueDateMatched) {
                LoggerService::info('Skipping dtt follow-up because comparison date is not a due follow-up date', [
                    'dtt_revival_id' => $this->dttRevival->id,
                    'child_quote_uuid' => $this->dttRevival->uuid,
                    'created_at' => (string) $created_at,
                    'comparison_date' => $today->toDateString(),
                ]);

                return false;
            }

            if (! $attemptedSend) {
                LoggerService::info('Skipping dtt follow-up due to follow_up_email_count mismatch', [
                    'dtt_revival_id' => $this->dttRevival->id,
                    'child_quote_uuid' => $this->dttRevival->uuid,
                    'expected_follow_up_email_count' => $expectedFollowUpCount,
                    'actual_follow_up_email_count' => (int) $this->dttRevival->follow_up_email_count,
                ]);

                return false;
            }
        }

    }

    private function sendFollowUpEmail($emailData)
    {
        LoggerService::info('CarRevivalFollowUpEmailJobOld email is sent '.$this->dttRevival->uuid.' - '.$emailData->customerEmail);
        $response = app(SendEmailCustomerService::class)->sendDttEmail($emailData);
        if ($response == 201) {
            DttRevival::where('id', $this->dttRevival->id)->increment('follow_up_email_count');
            LoggerService::info('CarRevivalFollowUpEmailJobOld email is sent '.$this->dttRevival->uuid.' - '.$emailData->customerEmail);
        } else {
            LoggerService::info('CarRevivalFollowUpEmailJobOld email not sent '.$this->dttRevival->uuid.' - '.$emailData->customerEmail);
        }
    }
}
