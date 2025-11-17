<?php

namespace App\Exports;

use App\Enums\quoteStatusCode;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeShortCode;
use App\Traits\ExcelExportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class RenewalQuotesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStrictNullComparison
{
    use ExcelExportable;

    public $query;

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
            'Previous Policy number',
            'Previous Policy start date',
            'Previous Policy expiry date',
            'Previous Gross premium',
            'Previous advisor',
            'Previous Commission',
            'Lead Level PC Tag',
            'Customer Level PC Tag',
            $this->exportType == 'BUSINESS' ? 'Business Type' : '',
        ];
    }

    public function map($quote): array
    {
        /**
         * Optimize payment retrieval to minimize queries and memory usage.
         * Assumes that the necessary relationships are eager loaded in the query builder:
         * - For CAR: previousQuote.payments
         * - For others: payments
         * This avoids N+1 queries and unnecessary loading.
         */
        $payment = null;
        if ($this->exportType === QuoteTypeShortCode::CAR) {
            // Use loaded relationship if available, avoid triggering additional queries
            if (isset($quote->previousQuote) && $quote->previousQuote && $quote->previousQuote->relationLoaded('payments')) {
                $payment = $quote->previousQuote->payments->first();
            }
        } else {
            if ($quote->relationLoaded('payments')) {
                $payment = $quote->payments->first();
            }
        }

        return [
            $quote->code,
            $quote->customer_id,
            $quote->first_name.' '.$quote->last_name,
            $quote->currentlyInsuredWith != null ? ($quote->currentlyInsuredWith->text ? $quote->currentlyInsuredWith->text : $quote->currentlyInsuredWith) : ($quote->currently_insured_with != null ? $quote->currently_insured_with : ''),
            $this->exportType,
            $quote->previous_quote_policy_number,
            $quote->previous_policy_start_date,
            $quote->previous_policy_expiry_date,
            $quote->previous_quote_policy_premium,
            $quote->previousAdvisor != null ? $quote->previousAdvisor->name : '',
            $payment != null ? $payment->commission : 'N/A',
            (isset($quote->pc_qualified) && $quote->pc_qualified == 1) ? 'Yes' : 'No',
            $quote->customer?->pcp_tag == 1 ? 'Yes' : 'No',
            $this->exportType == 'BUSINESS' ? ($quote->business_type_of_insurance_id == 5 ? quoteStatusCode::GROUP_MEDICAL : quoteTypeCode::CORPLINE) : '',
        ];
    }

    public function collection($requestParams = [])
    {
        return $this->query->get();
    }
}
