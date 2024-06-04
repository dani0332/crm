<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Models\ApplicationStorage;
use App\Models\HealthQuote;
use App\Services\CapiRequestService;
use App\Traits\AddPremiumAllLobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

class HealthRevivalLeadsCreationJob implements ShouldQueue, StackableJob
{
    use AddPremiumAllLobs, Dispatchable, InteractsWithQueue, Queueable, Stackable;

    public $tries = 3;
    public $timeout = 90;
    public $backoff = 300;
    private $lead = null;

    /**
     * Create a new job instance.
     */
    public function __construct($lead)
    {
        $this->lead = $lead;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $dttEnabled = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::DTT_ENABLED)->value('value');
        if ($dttEnabled == 0) {
            info('CarRevivalLeadsCreationJob - Dtt is not enabled from cms');

            return false;
        }

        $this->lead->refresh();
        $logPrefix = 'HealthRevivalLeadsCreationJob-';
        try {

            $dataArr = [
                // 'email' =>  $this->lead->email,
                'email' => 'nouman.hussain@myalfred.com',
                'details' => $this->lead->details,
                'mobileNo' => $this->lead->mobile_no,
                'preference' => $this->lead->preference,
                'source' => LeadSourceEnum::REVIVAL,
                'maritalStatusId' => $this->lead->marital_status_id,
                'premium' => $this->lead->premium,
                'leadTypeId' => $this->lead->lead_type_id,
                'referenceUrl' => config('constants.APP_URL'),
                'is_ebp_renewal' => $this->lead->is_ebp_renewal == 'on' ? true : false,
                'coverForId' => $this->lead->cover_for_id,
                'hasDental' => $this->lead->has_dental == 'on' ? true : false,
                'hasWorldwideCover' => $this->lead->has_worldwide_cover == 'on' ? true : false,
                'hasHome' => $this->lead->has_home == 'on' ? true : false,
                'currentlyInsuredWithId' => $this->lead->currently_insured_with_id,
            ];
            $dataArr['memberDetails'][] = [
                'firstName' => $this->lead->first_name,
                'lastName' => $this->lead->last_name,
                'dob' => $this->lead->dob,
                'gender' => $this->lead->gender,
                'nationalityId' => $this->lead->nationality_id,
                'emirateOfYourVisaId' => $this->lead->emirate_of_your_visa_id,
                'salaryBandId' => $this->lead->salary_band_id,
                'memberCategoryId' => $this->lead->member_category_id,
            ];


            info($logPrefix . 'capiPayLoad' . json_encode($dataArr));


            $capiResponse = CapiRequestService::sendCAPIRequest('/api/v1-save-health-quote', $dataArr, HealthQuote::class);


            if (!isset($capiResponse->errors) && !empty($capiResponse->quoteUID)) {
                info('carRevivalParentLead -' . $this->lead->uuid . '- childLeadCreated - ' . $capiResponse->quoteUID . ' - CAPI Response-' . json_encode($capiResponse));
            } else {
                info('healthRevivalLeadsCreationJob - healthRevivalParentLead -' . $this->lead->uuid . '- capiResponseError - ' . json_encode($capiResponse));
            }
        } catch (\Exception $exception) {
            Log::error($logPrefix.'health revival Exception - '.$this->lead->id.' - Exception:'.$exception->getMessage());
        }
    }
}
