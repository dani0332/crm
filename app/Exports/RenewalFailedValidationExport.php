<?php

namespace App\Exports;

use App\Enums\RenewalProcessStatuses;
use App\Models\RenewalQuoteProcess;
use Maatwebsite\Excel\Concerns\FromCollection;

class RenewalFailedValidationExport implements FromCollection
{
    private $renewalUploadLeadsId;

    public function __construct($renewalUploadLeadsId)
    {
        $this->renewalUploadLeadsId = $renewalUploadLeadsId;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $failedLeads = RenewalQuoteProcess::where('renewals_upload_lead_id', $this->renewalUploadLeadsId)->whereIn('status', [RenewalProcessStatuses::BAD_DATA, RenewalProcessStatuses::VALIDATION_FAILED])->get();
        $exportLeads = collect();
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
