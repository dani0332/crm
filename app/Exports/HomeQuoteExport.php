<?php

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Repositories\HomeQuoteRepository;
use App\Traits\ModernCsvExportable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class HomeQuoteExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    public function __construct(
        private HomeQuoteRepository $homeQuoteRepository
    ) {}

    public function collection(array $requestParams = []): Collection
    {
        return $this->homeQuoteRepository->fetchGetData(true, false, $requestParams)->get();
    }

    public function getQuery(array $requestParams = []): ?Builder
    {
        return $this->homeQuoteRepository->fetchGetData(true, false, $requestParams);
    }

    public function headings(): array
    {
        return [
            'REF-ID',
            'FIRST NAME',
            'LAST NAME',
            'LEAD STATUS',
            'ADVISOR',
            'CREATED DATE',
            'ADVISOR ASSIGNED DATE',
            'LAST MODIFIED DATE',
            'TRANSAPP CODE',
            'SOURCE',
            'LOST REASON',
            'PREMIUM',
            'POLICY NUMBER',
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
            date(config('constants.datetime_format'), strtotime($quote->created_at)),
            isset($quote->homeQuoteRequestDetail->advisor_assigned_date) ? date(config('constants.datetime_format'), strtotime($quote->homeQuoteRequestDetail->advisor_assigned_date)) : '',
            date(config('constants.datetime_format'), strtotime($quote->updated_at)),
            optional($quote->homeQuoteRequestDetail)->transapp_code,
            $quote->source,
            optional($quote->homeQuoteRequestDetail)->lostReason?->text,
            ! empty($quote->premium) ? $quote->premium : $quote->price_with_vat,
            $quote->policy_number,
            $quote->renewal_batch,
            $quote->previous_policy_expiry_date ? date('d-M-Y', strtotime($quote->previous_policy_expiry_date)) : '',
            $quote->previous_quote_policy_premium ? $quote->previous_quote_policy_premium : '',
            $quote->previous_quote_policy_number ? $quote->previous_quote_policy_number : '',
            $quote->transaction_approved_at ? date(config('constants.datetime_format'), strtotime($quote->transaction_approved_at)) : '',
            $quote->policy_booking_date ? date(config('constants.datetime_format'), strtotime($quote->policy_booking_date)) : '',
        ];
    }

    public function getExportMetadata(array $requestParams = []): array
    {
        return [
            'exportClass' => static::class,
            'timestamp' => now()->toISOString(),
            'parameters' => $requestParams,
            'sourceTable' => 'personal_quotes',
            'quoteTypeId' => 2, // QuoteTypeId::Home
            'exportType' => 'home_quotes',
        ];
    }
}
