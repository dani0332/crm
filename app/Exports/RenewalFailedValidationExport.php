<?php

namespace App\Exports;

use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Models\RenewalQuoteProcess;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class RenewalFailedValidationExport implements FromCollection, WithStrictNullComparison
{
    private $renewaUploadLead;

    public function __construct($renewalUploadLead)
    {
        $this->renewaUploadLead = $renewalUploadLead;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $failedLeads = RenewalQuoteProcess::where('renewals_upload_lead_id', $this->renewaUploadLead->id)->whereIn('status', [RenewalProcessStatuses::BAD_DATA, RenewalProcessStatuses::VALIDATION_FAILED])->get();
        $exportLeads = collect();
        if ($this->renewaUploadLead->renewal_import_type == RenewalsUploadType::CREATE_LEADS) {
            $firstRow = (object) [];
            $firstRow->customer_name = 'Customer Name';
            $firstRow->email = 'Customer e-mail';
            $firstRow->mobile_no = 'Customer Mobile';
            $firstRow->quote_type = 'Insurance Type';
            $firstRow->insurer = 'Insurance Provider';
            $firstRow->product = 'Product';
            $firstRow->product_type = 'Product Type';
            $firstRow->advisor = 'Advisor Email';
            $firstRow->policy_number = 'Policy Number';
            $firstRow->start_date = 'Policy Start Date';
            $firstRow->end_date = 'Policy End date';
            $firstRow->batch = 'Batch';
            $firstRow->make = 'Car Make';
            $firstRow->model = 'Car Model';
            $firstRow->year = 'Model Year';
            $firstRow->previous_advisor = 'Previous Advisor Email';
            $firstRow->object = 'Object';
            $firstRow->previous_quote_policy_premium = 'Gross Premium';
            $firstRow->source = 'Sales channel';
            $firstRow->notes = 'Notes';
            $firstRow->errors = 'Errors';
            $exportLeads->push($firstRow);
        } elseif ($this->renewaUploadLead->renewal_import_type == RenewalsUploadType::UPDATE_LEADS) {
            $firstRow = (object) [];
            $firstRow->customer_name = 'Customer Name';
            $firstRow->email = 'Customer e-mail';
            $firstRow->mobile_no = 'Customer Mobile';
            $firstRow->quote_type = 'Insurance Type';
            $firstRow->insurer = 'Insurance Provider';
            $firstRow->product_type = 'Product Type';
            $firstRow->advisor = 'Advisor Email';
            $firstRow->policy_number = 'Policy Number';
            $firstRow->end_date = 'Policy End date';
            $firstRow->batch = 'Batch';
            $firstRow->make = 'Car Make';
            $firstRow->model = 'Car Model';
            $firstRow->year = 'Model Year';
            $firstRow->dob = 'Date of Birth';
            $firstRow->driving_experience = 'Driving Experience';
            $firstRow->nationality = 'Nationality';
            $firstRow->provider_name = 'Provider Name';
            $firstRow->plan_name = 'Plan Name';
            $firstRow->plan_type = 'Repair Type';
            $firstRow->claim_history = 'Claims History';
            $firstRow->nc_letter = 'NC Letter';
            $firstRow->insurer_quote_no = 'Insurer Quote No.';
            $firstRow->car_value = 'Car Value (From Insurer)';
            $firstRow->premium = 'Renewal Premium';
            $firstRow->excess = 'Excess';
            $firstRow->ancillary_excess = 'Ancillary Excess';
            $firstRow->driver_cover = 'PAB Driver';
            $firstRow->driver_cover_amount = 'Amount - PAB Driver';
            $firstRow->passenger_cover = 'PAB Passenger';
            $firstRow->passenger_cover_amount = 'Amount - PAB Passenger';
            $firstRow->car_hire = 'Rent a car';
            $firstRow->car_hire_amount = 'Amount- Rent a Car';
            $firstRow->oman_cover = 'Oman cover';
            $firstRow->oman_cover_amount = 'Amount - Oman cover';
            $firstRow->road_side_assistance = 'Road Side Assistance';
            $firstRow->road_side_assistance_amount = 'Amount - Road Side Assistenace';
            $firstRow->year_of_first_registration = 'First Year of Registration';
            $firstRow->trim = 'Trim';
            $firstRow->registration_location = 'Registration Location';
            $firstRow->previous_advisor = 'Previous Advisor Email';
            $firstRow->notes = 'Notes';
            $firstRow->is_gcc = 'Is GCC';
            $firstRow->errors = 'Errors';
            $exportLeads->push($firstRow);
        }
        foreach ($failedLeads as $lead) {
            if ($lead->data) {
                $leadData = $lead->data;
                $leadData['errors'] = $lead->validation_errors;
                $exportLeads->push($leadData);
            }
        }

        return $exportLeads;
    }
}
