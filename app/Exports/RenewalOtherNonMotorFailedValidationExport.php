<?php

namespace App\Exports;

use App\Enums\RenewalProcessStatuses;
use App\Models\RenewalQuoteProcess;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class RenewalOtherNonMotorFailedValidationExport implements FromCollection, WithStrictNullComparison
{
    public function __construct(private $renewalUploadLead) {}

    public function collection()
    {
        $failedLeads = RenewalQuoteProcess::where('renewals_upload_lead_id', $this->renewalUploadLead->id)
            ->whereIn('status', [RenewalProcessStatuses::BAD_DATA, RenewalProcessStatuses::VALIDATION_FAILED])
            ->get();

        $exportLeads = collect();
        $header = (object) [
            'ref_id' => 'Ref-ID',
            'advisor_email' => 'Advisor Email',
            'errors' => 'Error Message(s)',
        ];

        $exportLeads->push($header);

        foreach ($failedLeads as $lead) {
            $leadData = $lead->data ?? [];
            $exportLeads->push([
                'ref_id' => $leadData['ref_id'] ?? null,
                'advisor_email' => $leadData['advisor_email'] ?? null,
                'errors' => empty($lead->validation_errors)
                    ? 'No errors'
                    : implode("\n", (array) $lead->validation_errors),
            ]);
        }

        return $exportLeads;
    }
}
