<?php

namespace App\Services;

use Carbon\Carbon;

class EmailDataService extends BaseService
{
    protected $applicationStorageService;
    protected $lookupService;

    public function __construct(
        ApplicationStorageService $applicationStorageService,
        LookupService $lookupService
    ) {
        $this->applicationStorageService = $applicationStorageService;
        $this->lookupService = $lookupService;
    }

    public function generateTierREmailData($lead)
    {
        $emailDataArr = [];

        if ($lead) {
            $emailDataArr = [
                'clientFullName' => $lead->first_name.' '.$lead->last_name,
                'email' => isset($lead->email) ? $lead->email : '',
                'customerEmail' => isset($lead->email) ? $lead->email : '',
                'phone' => isset($lead->mobile_no) ? $lead->mobile_no : '',
                'nationality' => isset($lead->nationality_id) ? $this->lookupService->getNationality($lead->nationality_id)->text : '',
                'dob' => isset($lead->dob) ? Carbon::parse($lead->dob)->format('d-m-Y') : '',
                'yearsOfDriving' => isset($lead->uae_license_held_for_id) ? $this->lookupService->getUaeLicenseHeldFor($lead->uae_license_held_for_id)->text : '',
                'yearOfManufacturing' => isset($lead->year_of_manufacture) ? $lead->year_of_manufacture : '',
                'model' => isset($lead->car_model_id) ? $this->lookupService->getCarModel($lead->car_model_id)->text : '',
                'make' => isset($lead->car_make_id) ? $this->lookupService->getCarMake($lead->car_make_id)->text : '',
                'carValue' => isset($lead->car_value) ? number_format($lead->car_value, 2) : '0.00',
                'quoteLink' => config('constants.APP_URL').'/quotes/car/'.$lead->uuid,
            ];
        }

        return $emailDataArr;
    }
}
