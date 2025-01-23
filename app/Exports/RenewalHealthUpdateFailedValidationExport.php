<?php

namespace App\Exports;

use App\Enums\RenewalProcessStatuses;
use App\Models\RenewalQuoteProcess;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class RenewalHealthUpdateFailedValidationExport implements FromCollection, WithStrictNullComparison
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

        $firstRow = (object) [];
        $firstRow->customer_name = 'Customer Name';
        $firstRow->email = 'Customer Email';
        $firstRow->mobile_no = 'Customer Mobile';
        $firstRow->plan_code = 'Plan';
        $firstRow->policy_number = 'Previous Policy Number';
        $firstRow->end_date = 'Previous Policy Expiry Date';
        $firstRow->advisor = 'Advisor Email';
        $firstRow->member_premium = 'Renewal Premium';
        $firstRow->copay = 'Renewal Co-Pay';
        $firstRow->member_names = 'Member Names';
        $firstRow->member_dob = "Customer's DOB";
        $firstRow->member_nationality = "Customer's Nationality";
        $firstRow->gender = 'Gender';
        $firstRow->member_category = 'Member Category';
        $firstRow->member_emirate_of_visa = 'Emirate of Visa';
        $firstRow->payment_link = 'Payment Link';
        $firstRow->previous_policy_premium = 'Previous Policy Premium';
        $firstRow->notes = 'Notes';
        $exportLeads->push($firstRow);
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
