<?php

namespace App\Exports;

use App\Enums\QuoteTypes;
use App\Repositories\BusinessQuoteRepository;
use App\Traits\ExcelExportable;

class AmtQuoteExport
{
    use ExcelExportable;

    public function collection()
    {
        return BusinessQuoteRepository::getData(QuoteTypes::GROUP_MEDICAL->value, true);
    }

    public function headings(): array
    {
        return [
            'REF-ID',
            'FIRST NAME',
            'LAST NAME',
            'LEAD STATUS',
            'ADVISOR',
            'PREMIUM',
            'COMPANY NAME',
            'POLICY NUMBER',
            'LOST REASON',
            'SOURCE',
            'CREATED DATE',
            'LAST MODIFIED DATE',
            'RENEWAL BATCH',
            'PREVIOUS POLICY EXPIRY DATE',
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
            $quote->premium,
            $quote->company_name,
            $quote->policy_number,
            optional($quote->businessQuoteRequestDetail)->lostReason?->text,
            $quote->source,
            date(config('constants.datetime_format'), strtotime($quote->created_at)),
            date(config('constants.datetime_format'), strtotime($quote->updated_at)),
            $quote->renewal_batch,
            $quote->previous_policy_expiry_date ? date('d-M-Y', strtotime($quote->previous_policy_expiry_date)) : '',
        ];
    }
}
