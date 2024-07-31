<?php

namespace App\Exports;

use App\Repositories\HomeQuoteRepository;
use App\Traits\ExcelExportable;

class HomeQuoteExport
{
    use ExcelExportable;

    public function collection()
    {
        return HomeQuoteRepository::getData(true);
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
            'LAST MODIFIED DATE',
            'TRANSAPP CODE',
            'SOURCE',
            'LOST REASON',
            'PREMIUM',
            'POLICY NUMBER',
            'RENEWAL BATCH',
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
            date(config('constants.datetime_format'), strtotime($quote->updated_at)),
            optional($quote->homeQuoteRequestDetail)->transapp_code,
            $quote->source,
            optional($quote->homeQuoteRequestDetail)->lostReason?->text,
            $quote->premium,
            $quote->policy_number,
            $quote->renewal_batch,
        ];
    }
}
