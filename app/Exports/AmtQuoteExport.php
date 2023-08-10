<?php

namespace App\Exports;

use App\Enums\QuoteTypes;
use App\Repositories\BusinessQuoteRepository;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AmtQuoteExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

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
            date('d-m-Y H:i:s', strtotime($quote->created_at)),
            date('d-m-Y H:i:s', strtotime($quote->updated_at)),
        ];
    }
}
