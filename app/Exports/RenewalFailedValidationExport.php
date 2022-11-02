<?php

namespace App\Exports;

use App\Enums\RenewalProcessStatuses;
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
        if ($this->renewaUploadLead->renewal_import_type == 'create') {
            $firstRow = (object) [];
            $firstRow->customer_name = 'Customer Name';
            $firstRow->email = 'Customer e-mail';
            $firstRow->quote_type = 'Type';
            $firstRow->insurer = 'Insurer';
            $firstRow->product = 'Product';
            $firstRow->product_type = 'Product Type';
            $firstRow->source = 'Sales channel';
            $firstRow->mobile_no = 'Customer mobile';
            $firstRow->advisor = 'Advisor';
            $firstRow->previous_advisor = 'Previous Advisor';
            $firstRow->policy_number = 'Policy';
            $firstRow->batch = 'Batch';
            $firstRow->start_date = 'Start Date';
            $firstRow->end_date = 'End Date';
            $firstRow->object = 'Object';
            $firstRow->premium = 'Gross Premium';
            $firstRow->notes = 'Notes';
            $firstRow->make = 'Make';
            $firstRow->model = 'Model';
            $firstRow->year = 'Year';
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
