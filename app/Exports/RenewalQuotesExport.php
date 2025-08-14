<?php

namespace App\Exports;

use App\Enums\quoteStatusCode;
use App\Enums\quoteTypeCode;
use App\Traits\ExcelExportable;

class RenewalQuotesExport
{
    use ExcelExportable;

    public $exportType;

    public function __construct($query, $exportType)
    {
        $this->query = $query;
        $this->exportType = $exportType;

    }

    public function headings(): array
    {
        return [
            'Ref-ID',
            'Customer ID',
            'Customer Name',
            'Currently insured with',
            'Product',
            'Previous Policy start date',
            'Previous Policy expiry date',
            'Previous Gross premium',
            'Previous advisor',
            'Commission',
            $this->exportType == 'BUSINESS' ? 'Business Type' : '',
            'PC Tag'
        ];
    }

    public function map($quote): array
    {
        $payment = $quote->payments->first();

        return [
            $quote->code,
            $quote->customer_id,
            $quote->first_name.' '.$quote->last_name,
            $quote->currentlyInsuredWith != null ? ($quote->currentlyInsuredWith->text ? $quote->currentlyInsuredWith->text : $quote->currentlyInsuredWith) : ($quote->currently_insured_with != null ? $quote->currently_insured_with : ''),
            $this->exportType,
            $quote->previous_policy_start_date,
            $quote->previous_policy_expiry_date,
            $quote->previous_quote_policy_premium,
            $quote->previousAdvisor != null ? $quote->previousAdvisor->name : '',
            $payment != null ? $payment->commission : 'N/A',
            $this->exportType == 'BUSINESS' ? ($quote->business_type_of_insurance_id == 5 ? quoteStatusCode::GROUP_MEDICAL : quoteTypeCode::CORPLINE) : '',
            (isset($quote->pc_qualified) && $quote->pc_qualified == 1 ) ? 'Yes' : 'No',
        ];
    }

    public function collection($requestParams = [])
    {
        return $this->query->get();
    }
}
