<?php

namespace App\Jobs;

use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Facades\Ken;
use App\Models\CarQuote;
use App\Models\DttRevival;
use App\Services\SendEmailCustomerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use PDO;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Throwable;

class CarRevivalLeadsCreationJob implements ShouldQueue, StackableJob
{
    use Stackable, Dispatchable, InteractsWithQueue, Queueable;
    use GenericQueriesAllLobs;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 300;
    private $lead = null;
    protected $sendEmailCustomerService;
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
    public function handle(SendEmailCustomerService $sendEmailCustomerService)
    {
        $this->sendEmailCustomerService = $sendEmailCustomerService;
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
            'carValue' => (int)$this->lead->car_value,
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

        info('CarRevivalLeadsCreationJob - Parent Lead Id  ' . $this->lead->id);

        $capiResponse = Capi::request('/api/v1-save-car-quote', 'post', $dataArr);

        if (!isset($capiResponse->errors) && !empty($capiResponse->quoteUID)) {
            info('CarRevivalLeadsCreationJob - Lead Created -' . $capiResponse->quoteUID . ' - CAPI Response:');
            // $plansDataArr = $this->payLoadForPlans($capiResponse->quoteUID);
            $carQuote = $this->getQuoteObject(QuoteTypes::CAR->value, $capiResponse->quoteUID);
            // Ken::request('/get-car-quote-plans', 'post', $plansDataArr);
            // dispatch(new SendOCBEmailJob($capiResponse->quoteUID, 1));

            $customerName = $carQuote->first_name . ' ' . $carQuote->last_name;
            $emailData = (object) [
                'quoteId' => $carQuote->id,
                'templateId' => 296,
                'customerName' => $customerName,
                'customerEmail' => $carQuote->email,
                'buttonUrl' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL') . $carQuote->uuid,
                'subject' => $customerName . "'s" . ' Car Insurance with Alfred ' . $carQuote->code
            ];
            $response = $this->sendEmailCustomerService->sendDttEmail($emailData, 'send-digital-transformation-email');

            if ($response == 201) {

                info('CarRevivalLeadsCreationJob - UUID - ' . $emailData->customerEmail . ' - Email Sent');

                DttRevival::create([
                    'quote_type_id' => QuoteTypes::CAR->id(),
                    'quote_id' => $carQuote->id,
                    'uuid' => $capiResponse->quoteUID,
                    'email_sent' => true,
                ]);

                info('CarRevivalLeadsCreationJob- Dtt Revivals inserted - UUID -' . $capiResponse->quoteUID);

                CarQuote::find($this->lead->id)->update(['is_revived' => true]);

                info('CarRevivalLeadsCreationJob- is_revived updated -' . $this->lead->id);
            } else {
                info('CarRevivalLeadsCreationJob- email is not sent -' . $emailData->customerEmail);
            }
        } else {

            info('CarRevivalLeadsCreationJob - Lead Not generated - capi response' . json_encode($capiResponse));
        }
    }

    private function payLoadForPlans($quoteUuId)
    {
        return [

            'quoteUID' => $quoteUuId,
            'getLatestRating' => false,
            'filters' => [[
                'field' => 'isRenewalSort',
                'value' => false,
            ]],
        ];
    }

    public function failed(Throwable $exception)
    {
        info('CarRevivalLeadsCreationJob -: ' . $this->lead->id . ' Error: ' . $exception->getMessage());
    }
}
