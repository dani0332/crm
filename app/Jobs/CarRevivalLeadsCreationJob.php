<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypes;
use App\Enums\TiersEnum;
use App\Facades\Capi;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Models\DttRevival;
use App\Models\Tier;
use App\Services\CarEmailService;
use App\Services\CarQuoteService;
use App\Services\CRUDService;
use App\Services\SendEmailCustomerService;
use App\Services\UserService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Throwable;

class CarRevivalLeadsCreationJob implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, Stackable;
    use GenericQueriesAllLobs;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 300;
    private $lead = null;
    protected $sendEmailCustomerService;
    protected $carQuoteService;
    protected $crudService;
    protected $userService;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($lead)
    {
        $this->lead = $lead;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(SendEmailCustomerService $sendEmailCustomerService, CarQuoteService $carQuoteService, CRUDService $crudService, UserService $userService)
    {
        $this->carQuoteService = $carQuoteService;
        $this->sendEmailCustomerService = $sendEmailCustomerService;
        $this->crudService = $crudService;

        $this->userService = $userService;
        $dataArr = [
            'firstName' => $this->lead->first_name,
            'lastName' => $this->lead->last_name,
            'email' => $this->lead->email,
            // 'email' => 'diana.gonzaga@insurancemarket.ae',
            // 'email' => 'nouman.hussain@insurancemarket.ae',
            'address' => $this->lead->address,
            'mobileNo' => $this->lead->mobile_no,
            'dob' => $this->lead->dob,
            'nationalityId' => $this->lead->nationality_id,
            'uaeLicenseHeldForId' => $this->lead->uae_license_held_for_id,
            'backHomeLicenseHeldForId' => $this->lead->back_home_license_held_for_id,
            'yearOfManufacture' => $this->lead->year_of_manufacture,
            'emirateOfRegistrationId' => $this->lead->emirate_of_registration_id,
            'carTypeInsuranceId' => $this->lead->car_type_insurance_id,
            'claimHistoryId' => $this->lead->claim_history_id,
            'hasNcdSupportingDocuments' => $this->lead->has_ncd_supporting_documents == GenericRequestEnum::Yes ? true : false,
            'additionalNotes' => $this->lead->additional_notes,
            'carValue' => (int) $this->lead->car_value,
            'carValueTier' => $this->lead->car_value_tier,
            'seatCapacity' => $this->lead->seat_capacity,
            'cylinder' => $this->lead->cylinder,
            'vehicleTypeId' => $this->lead->vehicle_type_id,
            'trim' => $this->lead->trim,
            'premium' => $this->lead->premium,
            'carMakeId' => $this->lead->car_make_id,
            'carModelId' => $this->lead->car_model_id,
            'currentlyInsuredWith' => $this->lead->currently_insured_with,
            'source' => LeadSourceEnum::REVIVAL,
            'isEmailSkip' => true,
            'referenceUrl' => config('constants.APP_URL'),
        ];

        info('CarRevivalLeadsCreationJob - Parent Lead Id  '.$this->lead->id);

        $capiResponse = Capi::request('/api/v1-save-car-quote', 'post', $dataArr);

        if (! isset($capiResponse->errors) && ! empty($capiResponse->quoteUID)) {
            info('CarRevivalLeadsCreationJob - Lead Created -'.$capiResponse->quoteUID.' - CAPI Response:');

            $carQuote = $this->getQuoteObject(QuoteTypes::CAR->value, $capiResponse->quoteUID);

            $listQuotePlans = $this->carQuoteService->getPlans($capiResponse->quoteUID, true, true);

            $quotePlansCount = is_countable($listQuotePlans) ? count($listQuotePlans) : 0;

            if ($quotePlansCount > 0) {
                $key = ApplicationStorageEnums::OCB_NEW_BUSINESS_SINGLE_MULTIPLE_PLANS;
            } else {
                $key = ApplicationStorageEnums::OCB_NEW_BUSINESS_ZERO_PLANS;
            }
            $emailTemplateId = ApplicationStorage::where('key_name', $key)->value('value');

            info('CarRevivalLeadsCreationJob: emailTemplateId '.json_encode($emailTemplateId));
            $previousAdvisor = null;
            if (! empty($carQuote->previous_advisor_id)) {
                $previousAdvisor = $this->userService->getUserById($carQuote->previous_advisor_id);
            }

            info('CarRevivalLeadsCreationJob: previousAdvisor '.json_encode($previousAdvisor));
            $tierR = Tier::where('name', TiersEnum::TIER_R)->where('is_active', 1)->first();

            $listQuotePlans = (is_string($listQuotePlans)) ? [] : $listQuotePlans;

            $emailData = (new CarEmailService($this->sendEmailCustomerService))->buildEmailData($carQuote, $listQuotePlans, $previousAdvisor, $tierR->id);

            $customerName = $carQuote->first_name.' '.$carQuote->last_name;

            $emailData->subject = $customerName."'s".' Car Insurance with Alfred '.$carQuote->code;

            $emailData->templateId = (int) $emailTemplateId;

            $emailData->advisorName = 'Alfred';
            $emailData->advisorEmail = 'askalfred@insurancemarket.ae';

            info('CarRevivalLeadsCreationJob: EmailData '.json_encode($emailData));

            $response = $this->sendEmailCustomerService->sendDttEmail($emailData, 'car-quote-one-click-buy-batch');

            info('CarRevivalLeadsCreationJob: emailResponse '.json_encode($response));
            if ($response == 201) {

                info('CarRevivalLeadsCreationJob - UUID - '.$emailData->customerEmail.' - Email Sent');

                DttRevival::create([
                    'quote_type_id' => QuoteTypes::CAR->id(),
                    'quote_id' => $carQuote->id,
                    'uuid' => $capiResponse->quoteUID,
                    'email_sent' => true,
                ]);

                info('CarRevivalLeadsCreationJob- Dtt Revivals inserted - UUID -'.$capiResponse->quoteUID);

                CarQuote::find($this->lead->id)->update(['is_revived' => true]);

                info('CarRevivalLeadsCreationJob- is_revived updated -'.$this->lead->id);
            } else {
                info('CarRevivalLeadsCreationJob- email is not sent -'.$emailData->customerEmail);
            }
        } else {

            info('CarRevivalLeadsCreationJob - Lead Not generated - capi response'.json_encode($capiResponse));
        }
    }

    public function failed(Throwable $exception)
    {
        info('CarRevivalLeadsCreationJob -: '.$this->lead->id.' Error: '.$exception->getMessage());
    }
}
