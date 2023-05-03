<?php

namespace App\Jobs;

use App\Models\CarQuote;
use App\Services\CarQuoteService;
use App\Services\CRUDService;
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
    public $timeout = 30;
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

            if (isset($carQuote->advisor_id)) {
                $advisor = $this->userService->getUserById($carQuote->advisor_id);
                $advisorName = $advisor->name;
                $advisorEmail = $advisor->email;
                $advisorMobile = $advisor->mobile_no;
                $advisorLandline = $advisor->landline_no;
            }

            // Send Email Data
            $carMake = $this->lookupService->getCarMake($carQuote->car_make_id);
            $carModel = $this->lookupService->getCarModel($carQuote->car_model_id);
            $emailData = (object) [
                'quoteId' => $carQuote->id,
                'templateId' => $emailTemplateId,
                'quoteCdbId' => $carQuote->code,
                'customerName' => $carQuote->first_name.' '.$carQuote->last_name,
                'customerEmail' => $carQuote->email,
                'previousPolicyExpiryDate' => $carQuote->previous_policy_expiry_date ?? null,
                'currentlyInsuredWith' => $carQuote->currently_insured_with,
                'carMake' => $carMake->text ?? null,
                'carModel' => isset($carModel->text) ? $carModel->text : null,
                'carManufactureYear' => $carQuote->year_of_manufacture,
                'previousPolicyNumber' => $carQuote->previous_quote_policy_number ?? null,
                'advisorName' => $advisorName ?? null,
                'advisorEmailAddress' => $advisorEmail ?? null,
                'advisorMobileNo' => $advisorMobile ?? null,
                'advisorLandlineNo' => $advisorLandline ?? null,
                'buttonUrl' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$carQuote->uuid,
                'listQuotePlans' => $listQuotePlans,
                'multipleQuoteUrl' => config('constants.AFIA_WEBSITE_DOMAIN').'/car-insurance/quote/'.$carQuote->uuid.'/'.'payment/?providerCode=',
                'quotePlansCount' => $quotePlansCount ?? 0,
            ];

            $responseCode = $this->sendEmailCustomerService->sendOcbEmail($emailTemplateId, $emailData, 'car-quote-one-click-buy-batch');

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
