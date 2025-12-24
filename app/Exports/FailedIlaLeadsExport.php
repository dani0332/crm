<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class FailedIlaLeadsExport implements FromCollection, WithStrictNullComparison
{
    private $leads;

    public function __construct($leads)
    {
        $this->leads = $leads;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $exportLeads = collect();
        
        // Add header row
 
        $firstRow = (object) [];

        $firstRow->code = 'Ref ID';
        $firstRow->first_name = 'First Name';
        $firstRow->last_name = 'Last Name';
        $firstRow->created_at = 'Created At';
        $firstRow->paid_at  = "Payment Authorised Date";
        $firstRow->quote_status = "Lead Status";
        $exportLeads->push($firstRow);

        // Add data rows
        foreach ($this->leads as $lead) {
            $leadData = (object) [];
            $leadData->code = $lead->code ?? '';
            $leadData->first_name = $lead->first_name ?? '';
            $leadData->last_name = $lead->last_name ?? '';
            $leadData->created_at = $lead->created_at ?? '';
            $leadData->paid_at = $lead->paid_at ?? '';
            $leadData->quote_status = $lead->quoteStatus->text ?? '';
            $exportLeads->push($leadData);
        }

        return $exportLeads;
    }
}