<?php

namespace App\Jobs;

use App\Enums\TiersEnum;
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
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOCBEmailJob implements ShouldQueue
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
    public $timeout = 60;
    public $backoff = 10;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($quoteUuid)
    {
        $this->quoteUuid = $quoteUuid;
        $this->onQueue('renewals');
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

            $listQuotePlans = $this->carQuoteService->getPlans($this->quoteUuid, true, true);

            $quotePlansCount = is_countable($listQuotePlans) ? count($listQuotePlans) : 0;

            $emailTemplateId = (int) $this->crudService->getOcbCustomerEmailTemplate($quotePlansCount);

            $previousAdvisor = null;
            if (! empty($carQuote->previous_advisor_id)) {
                $previousAdvisor = $this->userService->getUserById($carQuote->previous_advisor_id);
            }

            $tierR = Tier::where('name', TiersEnum::TIER_R)->where('is_active', 1)->first();

            $listQuotePlans = (is_string($listQuotePlans)) ? [] : $listQuotePlans;

            $emailData = (new CarEmailService($this->sendEmailCustomerService))->buildEmailData($carQuote, $listQuotePlans, $previousAdvisor, $tierR->id);

            $responseCode = $this->sendEmailCustomerService->sendRenewalsOcbEmail($emailTemplateId, $emailData, 'car-quote-one-click-buy-batch');

            if (in_array($responseCode, [200, 201])) {
                Log::info('SendOCBEmailJob - OCB Email Sent: '.$responseCode.' Customer Email Address: '.$carQuote->email.' Quote UuId: '.$this->quoteUuid);
            } else {
                Log::error('SendOCBEmailJob - OCB Email Not Sent: '.$responseCode.' Customer EmailAddress:'.$carQuote->email);
            }
        } catch (Exception $e) {
            Log::info('SendOCBEmailJob - Error: '.$e->getMessage());
        }
    }

    public function failed(Throwable $exception)
    {
        info('SendOCBEmailJob -: '.$this->quoteUuid.' Error: '.$exception->getMessage());
    }
}
