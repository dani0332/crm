<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\TiersEnum;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Models\Tier;
use App\Services\CarQuoteService;
use App\Services\CRUDService;
use App\Services\EmailServices\CarEmailService;
use App\Services\LookupService;
use App\Services\RenewalsUploadService;
use App\Services\SendEmailCustomerService;
use App\Services\UserService;
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

    protected $quoteUuid;
    protected $carQuoteService;
    protected $renewalUploadService;
    protected $crudService;
    protected $userService;
    protected $lookupService;
    protected $sendEmailCustomerService;
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
        CRUDService $crudService,
        UserService $userService,
        LookupService $lookupService,
        SendEmailCustomerService $sendEmailCustomerService,
        RenewalsUploadService $renewalsUploadFileService
    ) {

        $this->carQuoteService = $carQuoteService;
        $this->crudService = $crudService;
        $this->userService = $userService;
        $this->lookupService = $lookupService;
        $this->sendEmailCustomerService = $sendEmailCustomerService;
        $this->renewalUploadService = $renewalsUploadFileService;

        try {
            $carQuote = CarQuote::where('uuid', $this->quoteUuid)->firstOrFail();
            if (! $carQuote) {
                return false;
            }
            $listQuotePlans = $this->carQuoteService->getPlans($this->quoteUuid, true, true);

            $quotePlansCount = is_countable($listQuotePlans) ? count($listQuotePlans) : 0;

            $emailTemplateId = $this->getEmailTemplateId($carQuote, $quotePlansCount);

            $previousAdvisor = null;
            if (! empty($carQuote->previous_advisor_id)) {
                $previousAdvisor = $this->userService->getUserById($carQuote->previous_advisor_id);
            }

            $tierR = Tier::where('name', TiersEnum::TIER_R)->where('is_active', 1)->first();

            $listQuotePlans = (is_string($listQuotePlans)) ? [] : $listQuotePlans;

            $emailData = (new CarEmailService($this->sendEmailCustomerService))->buildEmailData($carQuote, $listQuotePlans, $previousAdvisor, $tierR->id);

            $senderDetail = isset($emailData->advisorEmail) && $emailData->advisorEmail != null && $emailData->advisorEmail != '' ? [
                'email' => strstr($emailData->advisorEmail, '@', true) . '@renewals.insurancemarket.ae',
                'name' => $emailData->advisorName,
            ]: [];

            $responseCode = $this->sendEmailCustomerService->sendEmail($emailTemplateId, $emailData, 'car-quote-one-click-buy-batch', [], $senderDetail);

            if (in_array($responseCode, [200, 201])) {
                Log::info('SendOCBEmailJob - OCB Email Sent: '.$responseCode.' Customer Email Address: '.$carQuote->email.' Quote UuId: '.$this->quoteUuid);
            } else {
                Log::error('SendOCBEmailJob - OCB Email Not Sent: '.$responseCode.' Customer EmailAddress:'.$carQuote->email);
            }
        } catch (Exception $e) {
            Log::info('SendOCBEmailJob - Error: '.$e->getMessage());
        }
    }

    private function getEmailTemplateId($carQuote, $quotePlansCount) 
    {
        $emailTemplateId = (int) $this->crudService->getOcbCustomerEmailTemplate($quotePlansCount);
        Log::info('fn: sendOcbEmailJob Renewals OCB Email email template id: ' . $emailTemplateId);

        if (isset($carQuote->advisor_id)) {
            $advisor = $this->userService->getUserById($carQuote->advisor_id);
        } else {
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
