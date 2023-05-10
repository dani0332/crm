<?php

namespace App\Jobs;

use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Facades\Capi;
use App\Facades\Ken;
use App\Models\CarMake;
use App\Models\CarQuote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Throwable;

class CarRevivalLeadsCreationJob implements ShouldQueue, StackableJob
{
    use Stackable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 15;
    public $backoff = 300;
    private $lead = null;

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
    public function handle()
    {
        Log::info('Car Lead id: '.$this->lead->id);

        $dataArr = [
            'firstName' => $this->lead->first_name,
            'lastName' => $this->lead->last_name,
            'email' => $this->lead->email,
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
            'carValue' => $this->lead->car_value_tier,
            'carValueTier' => $this->lead->car_value_tier,
            'seatCapacity' => $this->lead->seat_capacity,
            'cylinder' => $this->lead->cylinder,
            'vehicleTypeId' => $this->lead->vehicle_type_id,
            'trim' => $this->lead->trim,
            'premium' => $this->lead->premium,
            'carMakeId' => CarMake::where('code', $this->lead->car_make_id)->first() ? CarMake::where('code', $this->lead->car_make_id)->first()->id : null, // ID
            'carModelId' => $this->lead->car_model_id, // ID
            'currentlyInsuredWith' => $this->lead->currently_insured_with,
            'source' => LeadSourceEnum::REVIVAL,
            'referenceUrl' => config('constants.APP_URL'),
        ];

        Log::info('Car Lead Data: '.json_encode($dataArr));

        $capiResponse = Capi::request('/api/v1-save-car-quote', 'post', $dataArr);

        Log::info('capiResponse: '.json_encode($capiResponse));

        if (! isset($capiResponse->errors) && ! empty($capiResponse->quoteUID)) {
            $plansDataArr = $this->payLoadForPlans($capiResponse->quoteUID);

            Log::info('kenPayload: '.json_encode($plansDataArr));

            Ken::request('/get-car-quote-plans', 'post', $plansDataArr);

            dispatch(new SendOCBEmailJob($capiResponse->quoteUID));

            Log::info('OCB Email Job Dispatched for customer having '.$capiResponse->quoteUID);

            CarQuote::find($this->lead->id)->update(['is_revived' => true]);
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
        info('CarRevivalLeadsCreationJob -: '.$this->lead->id.' Error: '.$exception->getMessage());
    }
}
