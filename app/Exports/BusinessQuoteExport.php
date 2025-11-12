<?php

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Enums\QuoteTypes;
use App\Repositories\BusinessQuoteRepository;
use App\Traits\ModernCsvExportable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BusinessQuoteExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    public function collection(array $requestParams = []): Collection
    {
        return BusinessQuoteRepository::getData(QuoteTypes::CORPLINE->value, true, requestParams: $requestParams)->get();
    }

    /**
     * Get the query builder instance to use for chunking
     * This is the key to memory-efficient CSV exports
     */
    public function getQuery(array $requestParams = []): ?Builder
    {
        return BusinessQuoteRepository::getData(QuoteTypes::CORPLINE->value, true, requestParams: $requestParams);
    }

    public function headings(): array
    {
        return [
            'REF-ID',
            'FIRST NAME',
            'LAST NAME',
            'COMPANY NAME',
            'TRANSAPP CODE',
            'SOURCE',
            'POLICY NUMBER',
            'LOST REASON',
            'ADVISOR',
            'LEAD STATUS',
            'CREATED DATE',
            'ADVISOR ASSIGNED DATE',
            'LAST MODIFIED DATE',
            'PREMIUM',
            'NUMBER OF EMPLOYEES',
            'BUSINESS INSURANCE TYPE',
            'GENDER',
            'RENEWAL BATCH',
            'PREVIOUS POLICY EXPIRY DATE',
            'PREVIOUS POLICY PREMIUM',
            'PREVIOUS POLICY NUMBER',
            'TRANSACTION APPROVED DATE',
            'BOOKING DATE',
            'IMCRM SUB-SOURCE',
        ];
    }

    public function map($quote): array
    {
        return [
            $quote->code,
            $quote->first_name,
            $quote->last_name,
            $quote->company_name,
            optional($quote->businessQuoteRequestDetail)->transapp_code,
            $quote->source,
            $quote->policy_number,
            optional($quote->businessQuoteRequestDetail)->lostReason?->text,
            optional($quote->advisor)->name,
            optional($quote->quoteStatus)->text,
            date(config('constants.datetime_format'), strtotime($quote->created_at)),
            isset($quote->businessQuoteRequestDetail->advisor_assigned_date) ? date(config('constants.datetime_format'), strtotime($quote->businessQuoteRequestDetail->advisor_assigned_date)) : '',
            date(config('constants.datetime_format'), strtotime($quote->updated_at)),
            $quote->premium ? $quote->premium : $quote->price_with_vat,
            $quote->number_of_employees,
            optional($quote->businessTypeOfInsurance)->text,
            $quote->gender,
            $quote->renewal_batch,
            $quote->previous_policy_expiry_date ? date('d-M-Y', strtotime($quote->previous_policy_expiry_date)) : '',
            $quote->previous_quote_policy_premium ? $quote->previous_quote_policy_premium : '',
            $quote->previous_quote_policy_number ? $quote->previous_quote_policy_number : '',
            $quote->transaction_approved_at ? date(config('constants.datetime_format'), strtotime($quote->transaction_approved_at)) : '',
            $quote->policy_booking_date ? date(config('constants.datetime_format'), strtotime($quote->policy_booking_date)) : '',
            optional($quote->subSource)->text,
        ];
    }

    /**
     * Get export metadata with business-specific information
     */
    public function getExportMetadata(array $requestParams = []): array
    {
        return [
            'exportClass' => static::class,
            'timestamp' => now()->toISOString(),
            'parameters' => $requestParams,
            'sourceTable' => 'personal_quotes',
            'quoteTypeId' => 5, // QuoteTypeId::Business
            'exportType' => 'business_quotes',
        ];
    }
}
