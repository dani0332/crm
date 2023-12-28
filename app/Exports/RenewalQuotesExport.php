<?php

namespace App\Exports;

use App\Enums\quoteStatusCode;
use App\Enums\quoteTypeCode;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RenewalQuotesExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public $exportType;

    public function __construct($query, $exportType)
    {
        $this->query = $query;
        $this->exportType = $exportType;

    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Ref-ID',
            'Customer Name',
            'Currently insured with',
            'Product',
            'Policy start date',
            'Policy expiry date',
            'Gross premium',
            'Previous advisor',
            'Commission',
            $this->exportType == 'BUSINESS' ? 'Business Type' : '',
        ];
    }

    public function map($quote): array
    {
        $payment = $quote->payments->first();

        return [
            $quote->code,
            $quote->first_name.' '.$quote->last_name,
            $quote->currentlyInsuredWith != null ? ($quote->currentlyInsuredWith->text ? $quote->currentlyInsuredWith->text : $quote->currentlyInsuredWith) : ($quote->currently_insured_with != null ? $quote->currently_insured_with : ''),
            $this->exportType,
            $quote->policy_start_date,
            $quote->previous_policy_expiry_date,
            $quote->premium,
            $quote->previousAdvisor != null ? $quote->previousAdvisor->name : '',
            $payment != null ? $payment->commission : 'N/A',
            $this->exportType == 'BUSINESS' ? ($quote->business_type_of_insurance_id == 5 ? quoteStatusCode::GROUP_MEDICAL : quoteTypeCode::CORPLINE) : '',

        ];
    }
}
