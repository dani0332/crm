<?php

namespace App\Exports;

use App\Repositories\TravelQuoteRepository;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TravelQuoteExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

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
            $quote->dob,
            optional($quote->travelQuoteRequestDetail)->transapp_code,
            optional($quote->travelQuoteRequestDetail)->lostReason?->text,
            $quote->source,
            $quote->premium,
            $quote->policy_number,
            optional($quote->destination)->text,
            optional($quote->currentlyLocatedIn)->text,
            $quote->expiry_date,
            $quote->is_ecommerce ? 'Yes' : 'No',
            optional($quote->paymentStatus)->text,
        ];
    }
}
