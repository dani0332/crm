<?php

namespace App\Exports;

use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Models\RenewalQuoteProcess;
use Maatwebsite\Excel\Concerns\FromCollection;

class RenewalFailedValidationExport implements FromCollection
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
            $firstRow->car_value = 'Car Value (From Insurer)';
            $firstRow->premium = 'Renewal Premium';
            $firstRow->excess = 'Excess';
            $firstRow->trim = 'Trim';
            $firstRow->registration_location = 'Registration Location';
            $firstRow->previous_advisor = 'Previous Advisor Email';
            $firstRow->notes = 'Notes';
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
