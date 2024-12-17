<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\ThirdPartyTagEnum;
use App\Enums\TiersEnum;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Models\Tier;
use App\Services\BirdService;
use App\Services\CarQuoteService;
use App\Services\CRUDService;
use App\Services\EmailServices\CarEmailService;
use App\Services\SendEmailCustomerService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOCBEmailJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $quoteUuid;
    public $tries = 3;
    public $timeout = 90;
    public $backoff = 120;
    public $uniqueFor = 640;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($quoteUuid)
    {
        $this->quoteUuid = $quoteUuid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(
        CarQuoteService $carQuoteService,
        SendEmailCustomerService $sendEmailCustomerService
    ) {

        try {
            $carQuote = CarQuote::where('uuid', $this->quoteUuid)->firstOrFail();
            if (! $carQuote) {
                info('SendOCBEmailJob - OCB Email Not Sent - Car Quote Not Found '.$this->quoteUuid);

                return false;
            }
            $listQuotePlans = $carQuoteService->getPlans($this->quoteUuid, true, true);

            $quotePlansCount = is_countable($listQuotePlans) ? count($listQuotePlans) : 0;

            $emailTemplateId = $this->getEmailTemplateId($carQuote, $quotePlansCount);

            $previousAdvisor = null;
            if (! empty($carQuote->previous_advisor_id)) {
                $previousAdvisor = $carQuote->advisor;
            }

            $tierR = Tier::where('name', TiersEnum::TIER_R)->where('is_active', 1)->first();

            $listQuotePlans = (is_string($listQuotePlans)) ? [] : $listQuotePlans;

            $emailData = (new CarEmailService($sendEmailCustomerService))->buildEmailData($carQuote, $listQuotePlans, $previousAdvisor, $tierR->id);

            $responseCode = $this->sendEmail($carQuote, $emailTemplateId, $emailData, $sendEmailCustomerService);

            if (in_array($responseCode, [200, 201])) {
                Log::info('SendOCBEmailJob - OCB Email Sent: '.$responseCode.' Customer Email Address: '.$carQuote->email.' Quote UuId: '.$this->quoteUuid);
            } else {
                Log::error('SendOCBEmailJob - OCB Email Not Sent: '.$responseCode.' Customer EmailAddress:'.$carQuote->email);
            }
        } catch (Exception $e) {
            Log::info('SendOCBEmailJob - Error: '.$e->getMessage());
        }
    }

    /**
     * This function use to send email
     *
     * @param  CarQuote  $carQuote
     * @param  int  $emailTemplateId
     * @param  object  $emailData
     * @return int
     */
    private function sendEmail($carQuote, $emailTemplateId, $emailData, $sendEmailCustomerService)
    {
        Log::info('Renewals OCB Email sending for uuid: '.$carQuote->uuid);

        if (isset($carQuote->advisor_id)) {
            return $sendEmailCustomerService->sendRenewalsOcbEmail($emailTemplateId, $emailData, 'car-quote-one-click-buy-batch');
        } else {
            $responseCode = $sendEmailCustomerService->sendNonAdvisorIntroEmail($emailData, 'car-quote-one-click-buy-batch', $emailTemplateId);
            $this->triggerBirdWorkflow($emailData, $carQuote->mobile_no, $carQuote->uuid);

            return $responseCode;
        }
    }

    /**
     * This function use to trigger bird workflow
     *
     * @param  object  $emailData
     */
    private function triggerBirdWorkflow($emailData, $mobile, $uuid)
    {
        $birdEmailData = [
            'SendNewProcessRenewalEmail' => true,
            'customerEmail' => $emailData->customerEmail,
            'phone' => formatMobileNoWithoutPlus($mobile),
            'customerName' => $emailData->customerName,
            'quotePlanLink' => $emailData->quoteLink,
            'instantAlfredLink' => $emailData->quoteLink.'?IA=true',
            'refID' => $emailData->carQuoteId,
            'requestForAdvisor' => $emailData->requestAdvisorLink,
            'quoteUUID' => $uuid,
            'tag' => ThirdPartyTagEnum::BIRD_SIC_MOTOR_RENEWAL_TAG,
        ];

        $sicEvent = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_SIC_MOTOR_RENEWAL_WORKFLOW)->first();
        info('Renewals OCB Email No advisor: workflow trigger on BIRD, BIRD_SIC_MOTOR_RENEWAL_WORKFLOW value: '.$sicEvent->value);

        if ($sicEvent) {
            app(BirdService::class)->triggerWebHookRequest($sicEvent->value, $birdEmailData);
        }
    }

    private function getEmailTemplateId($carQuote, $quotePlansCount)
    {
        $emailTemplateId = (int) app(CRUDService::class)->getOcbCustomerEmailTemplate($quotePlansCount);
        Log::info('fn: sendOcbEmailJob Renewals OCB Email email template id: '.$emailTemplateId);

        if (! $carQuote->advisor_id) {
            $key = $this->getNoAdvisorKey($quotePlansCount);
            $noAdvisorTemplateId = ApplicationStorage::where('key_name', $key)->first();
            $emailTemplateId = $noAdvisorTemplateId ? (int) $noAdvisorTemplateId->value : 551;
        }

        return $emailTemplateId;
    }

    /**
     * This function use to get key for no advisor email template
     *
     * @param  int  $quotePlansCount
     * @return string
     */
    private function getNoAdvisorKey($quotePlansCount)
    {
        if ($quotePlansCount == 0) {
            return ApplicationStorageEnums::OCB_NEW_BUSINESS_ZERO_PLAN;
        } elseif ($quotePlansCount == 1) {
            return ApplicationStorageEnums::OCB_NEW_BUSINESS_SINGLE_PLAN;
        } else {
            return ApplicationStorageEnums::OCB_NEW_BUSINESS_MULTIPLE_PLANS;
        }
    }

    public function failed(Throwable $exception)
    {
        info('SendOCBEmailJob Failed: '.$this->quoteUuid.' Error: '.$exception->getMessage());
    }

    public function uniqueId(): string
    {
        return $this->quoteUuid;
    }
}
