<?php

namespace App\Jobs\Revival;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\TiersEnum;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Models\DttRevival;
use App\Models\Tier;
use App\Services\ApplicationStorageService;
use App\Services\CarQuoteService;
use App\Services\EmailServices\CarEmailService;
use App\Services\Logger\LoggerService;
use App\Services\MACRMService;
use App\Services\SendEmailCustomerService;
use App\Services\UserService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class CarRevivalFollowUpEmailJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 300;
    private $dttRevival = null;
    private $dttRevivalId = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($dttRevivalId)
    {
        $this->dttRevivalId = $dttRevivalId;
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

        $paymentStatusArray = [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED, PaymentStatusEnum::AUTHORISED];
        $leadSourceArray = [LeadSourceEnum::REVIVAL_PAID];
        $leadStatusArray = [QuoteStatusEnum::Duplicate, QuoteStatusEnum::Fake];

        $created_at = $this->dttRevival->created_at;
        $lead = CarQuote::where('uuid', $this->dttRevival->uuid)->first();

        // Follow-up emails will not dispatched if the payment status is either Authorised, Captured, Partial Captured
        // or if the source is Revival Paid or if the lead is assigned to an advisor
        if ($lead && ! empty($created_at) && ! in_array($lead->quote_status_id, $leadStatusArray) && ! in_array($lead->payment_status_id, $paymentStatusArray) && ! in_array($lead->source, $leadSourceArray) && empty($lead->advisor_id)) {
            try {
                $listQuotePlans = app(CarQuoteService::class)->getPlans($this->dttRevival->uuid, true, true);
            } catch (\Exception $exception) {
                LoggerService::info('DTTFolloupListQuotePlansException: '.$exception->getMessage());

                return false;
            }

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

            $emailData->carMake = (string) $lead?->car_make_id;
            $emailData->carModel = (string) $lead?->car_model_id;

            $voucherCode = MACRMService::generateMotorRevivalVoucherForQuote($lead);
            $emailData->voucherCode = $voucherCode ?? '';
            $emailData->myAlfredurl = filled($voucherCode)
                ? 'https://myalfred.com/voucher/'.rawurlencode($voucherCode)
                : '';

            $this->sendFollowUpEmail($emailData);
        }

    }

    private function sendFollowUpEmail($emailData)
    {
        // Migrate to Bird workflow - frequency is managed by Bird, but we update count here
        $workflowUrl = ApplicationStorage::where('key_name', ApplicationStorageEnums::MOTOR_REVIVAL_WORKFLOW)->first();

        if (! $workflowUrl || empty($workflowUrl->value)) {
            LoggerService::warning('CarRevivalFollowUpEmailJob - Bird workflow URL not configured; legacy Brevo send disabled', [
                'uuid' => $this->dttRevival->uuid,
            ]);

            return;
        }

        $response = app(SendEmailCustomerService::class)->sendDttEmailViaBird(
            $emailData,
            WorkflowTypeEnum::MOTOR_REVIVAL_FOLLOWUP,
            $workflowUrl->value
        );

        if ($response == 201) {
            DttRevival::where('id', $this->dttRevival->id)->increment('follow_up_email_count');
            LoggerService::info('CarRevivalFollowUpEmailJob email is sent via Bird '.$this->dttRevival->uuid.' - '.$emailData->customerEmail);
        } else {
            LoggerService::info('CarRevivalFollowUpEmailJob email not sent '.$this->dttRevival->uuid.' - '.$emailData->customerEmail);
        }
    }
}
