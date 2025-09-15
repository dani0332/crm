<?php

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Enums\QuoteTypes;
use App\Repositories\BusinessQuoteRepository;
use App\Traits\ModernCsvExportable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GroupMedicalExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    public function collection(array $requestParams = []): Collection
    {
        return BusinessQuoteRepository::getData(QuoteTypes::GROUP_MEDICAL->value, true, requestParams: $requestParams)->get();
    }

    /**
     * Get the query builder instance to use for chunking
     * This is the key to memory-efficient CSV exports
     */
    public function getQuery(array $requestParams = []): ?Builder
    {
        return BusinessQuoteRepository::getData(QuoteTypes::GROUP_MEDICAL->value, true, requestParams: $requestParams);
    }

    public function headings(): array
    {
        return [
            'REF-ID',
            'FIRST NAME',
            'LAST NAME',
            'LEAD STATUS',
            'ADVISOR',
            'OE / AE',
            'PREMIUM',
            'COMPANY NAME',
            'POLICY NUMBER',
            'LOST REASON',
            'SOURCE',
            'CREATED DATE',
            'ADVISOR ASSIGNED DATE',
            'LAST MODIFIED DATE',
            'RENEWAL BATCH',
            'PREVIOUS POLICY EXPIRY DATE',
            'PREVIOUS POLICY PREMIUM',
            'PREVIOUS POLICY NUMBER',
            'TRANSACTION APPROVED DATE',
            'BOOKING DATE',
        ];
    }

    public function map($quote): array
    {
        return [
            $quote->code,
            $quote->first_name,
            $quote->last_name,
            optional($quote->quoteStatus)->text,
            optional($quote->advisor)->name,
            optional($quote->supportUser)->name,
            $quote->premium ? $quote->premium : $quote->price_with_vat,
            $quote->company_name,
            $quote->policy_number,
            optional($quote->businessQuoteRequestDetail)->lostReason?->text,
            $quote->source,
            date(config('constants.datetime_format'), strtotime($quote->created_at)),
            isset($quote->businessQuoteRequestDetail->advisor_assigned_date) ? date(config('constants.datetime_format'), strtotime($quote->businessQuoteRequestDetail->advisor_assigned_date)) : '',
            date(config('constants.datetime_format'), strtotime($quote->updated_at)),
            $quote->renewal_batch,
            $quote->previous_policy_expiry_date ? date('d-M-Y', strtotime($quote->previous_policy_expiry_date)) : '',
            $quote->previous_quote_policy_premium ? $quote->previous_quote_policy_premium : '',
            $quote->previous_quote_policy_number ? $quote->previous_quote_policy_number : '',
            $quote->transaction_approved_at ? date(config('constants.datetime_format'), strtotime($quote->transaction_approved_at)) : '',
            $quote->policy_booking_date ? date(config('constants.datetime_format'), strtotime($quote->policy_booking_date)) : '',
        ];
    }

    /**
     * Get export metadata with Group Medical-specific information
     */
    public function getExportMetadata(array $requestParams = []): array
    {
        return [
            'exportClass' => static::class,
            'timestamp' => now()->toISOString(),
            'parameters' => $requestParams,
            'sourceTable' => 'personal_quotes',
            'quoteTypeId' => 6, // QuoteTypeId::GroupMedical
            'exportType' => 'group_medical_quotes',
        ];
    }
}
