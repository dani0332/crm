<?php

namespace App\Exports;

use App\Enums\quoteStatusCode;
use App\Enums\QuoteTypeShortCode;
use App\Traits\ExcelExportable;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
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
            'Previous Total Price with VAT',
            'Previous Commission',
            'Previous advisor',
            'Lead Level PC Tag',
            'Customer Level PC Tag',
            'Nationality',
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
            $quote->currentlyInsuredWith?->text ?? $quote->currently_insured_with ?? $quote->personalQuote?->currentlyInsuredWith?->text ?? '',
            $this->exportType,
            $quote->previous_quote_policy_number,
            $quote->previous_policy_start_date ? Carbon::parse($quote->previous_policy_start_date)->format(config('constants.DATE_FORMAT_ONLY')) : null,
            $quote->previous_policy_expiry_date ? Carbon::parse($quote->previous_policy_expiry_date)->format(config('constants.DATE_FORMAT_ONLY')) : null,
            $quote->previous_quote_policy_premium,
            $quote->previous_quote_policy_commission ?? ($payment != null ? $payment->commission : 'N/A'),
            $quote->previousAdvisor != null ? $quote->previousAdvisor->name : '',
            (isset($quote->pc_qualified) && $quote->pc_qualified == 1) ? 'Yes' : 'No',
            $quote->customer?->pcp_tag == 1 ? 'Yes' : 'No',
            $quote->nationality?->text ?? 'N/A',
            $this->exportType == 'BUSINESS' ? ($quote->business_type_of_insurance_id == 5 ? quoteStatusCode::GROUP_MEDICAL : ($quote->businessTypeOfInsurance?->text ?? 'N/A')) : '',
        ];
    }

    public function collection($requestParams = [])
    {
        $relations = ['nationality', 'customer'];

        if (method_exists($this->query->getModel(), 'previousAdvisor')) {
            $relations[] = 'previousAdvisor';
        }

        if ($this->exportType !== QuoteTypeShortCode::CAR) {
            $relations[] = 'payments';
        } else {
            $relations[] = 'previousQuote.payments';
        }

        return $this->query->with($relations)->get();
    }
}
