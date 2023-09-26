<?php

namespace App\Exports;

use App\Enums\quoteStatusCode;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RenewalQuotesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
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
            'Insurance provider',
            'Product',
            'Policy start date',
            'Policy expiry date',
            'Gross premium',
            'Previous advisor',
            $this->exportType=='BUSINESS'?'Business Type':'',
        ];
    }

    public function map($quote): array
    {

        return [
            $quote->code,
            $quote->insuranceProvider != null ? $quote->insuranceProvider->text : '',
            $this->exportType,
            $quote->policy_start_date,
            $quote->previous_policy_expiry_date,
            $quote->premium,
            $quote->previousAdvisor != null ? $quote->previousAdvisor->name : '',
            $this->exportType=='BUSINESS'?($quote->business_type_of_insurance_id == quoteStatusCode::GROUP_MEDICAL_ID?quoteStatusCode::GROUP_MEDICAL:quoteTypeCode::CORPLINE):''

        ];
    }
}
