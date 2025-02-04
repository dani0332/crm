<?php

namespace App\Exports;

use App\Repositories\LifeQuoteRepository;
use App\Traits\ExcelExportable;

class LifeQuotesExport
{
    use ExcelExportable;

    public function collection()
    {
        return LifeQuoteRepository::exportData();
    }

    public function headings(): array
    {
        return [
            'Ref-ID',
            'FIRST NAME',
            'LAST NAME',
            'LEAD STATUS',
            'ADVISOR',
            'CREATED DATE',
            'LAST MODIFIED DATE',
            'TRANSAPP CODE',
            'PREMIUM',
            'POLICY NUMBER',
            'SOURCE',
            'LOST REASON',
            'IS ECOMMERCE',
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
            optional($quote->quoteStatus)->text ?? '',
            optional($quote->advisor)->name,
            date(config('constants.datetime_format'), strtotime($quote->created_at)),
            date(config('constants.datetime_format'), strtotime($quote->updated_at)),
            $quote->transapp_code,
            $quote->premium ? $quote->premium : $quote->price_with_vat,
            $quote->policy_number,
            $quote->source,
            optional($quote->lifeQuoteRequestDetail)?->lostReason->text ?? '',
            $quote->is_ecommerce ? 'Yes' : 'No',
            $quote->renewal_batch,
            $quote->previous_policy_expiry_date ? date('d-M-Y', strtotime($quote->previous_policy_expiry_date)) : '',
            $quote->previous_quote_policy_premium ? $quote->previous_quote_policy_premium : '',
            $quote->previous_quote_policy_number ? $quote->previous_quote_policy_number : '',
            $quote->transaction_approved_at ? date(config('constants.datetime_format'), strtotime($quote->transaction_approved_at)) : '',
            $quote->policy_booking_date ? date(config('constants.datetime_format'), strtotime($quote->policy_booking_date)) : '',
        ];
    }
}
