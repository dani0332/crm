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
            'PREVIOUS POLICY EXPIRY DATE',
            'TRAVEL TYPE',
            'TRAVEL COVERAGE',
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
            $quote->previous_policy_expiry_date ? date('d-M-Y', strtotime($quote->previous_policy_expiry_date)) : '',
            $quote->direction_code,
            $quote->coverage_code,
            $quote->transaction_approved_at ? date(config('constants.datetime_format'), strtotime($quote->transaction_approved_at)) : '',
            $quote->policy_booking_date ? date(config('constants.datetime_format'), strtotime($quote->policy_booking_date)) : '',
        ];
    }
}
