<?php

namespace App\Exports;

use App\Repositories\TravelQuoteRepository;
use App\Traits\ExcelExportable;

class TravelQuoteExport
{
    use ExcelExportable;

    public function collection()
    {
        return TravelQuoteRepository::getData(true);
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
            'DOB',
            'TRANSAPP CODE',
            'LOST REASON',
            'SOURCE',
            'PREMIUM',
            'POLICY NUMBER',
            'DESTINATION',
            'CURRENTLY LOCATED IN',
            'EXPIRY DATE',
            'IS ECOMMERCE',
            'PAYMENT STATUS',
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
            date(config('constants.datetime_format'), strtotime($quote->dob)),
            optional($quote->travelQuoteRequestDetail)->transapp_code,
            optional($quote->travelQuoteRequestDetail)->lostReason?->text,
            $quote->source,
            $quote->premium,
            $quote->policy_number,
            optional($quote->destination)->text,
            optional($quote->currentlyLocatedIn)->text,
            date(config('constants.DATE_FORMAT'), strtotime($quote->expiry_date)),
            $quote->is_ecommerce ? 'Yes' : 'No',
            optional($quote->paymentStatus)->text,
            $quote->renewal_batch,
        ];
    }
}
