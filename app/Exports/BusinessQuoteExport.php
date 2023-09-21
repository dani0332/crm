<?php

namespace App\Exports;

use App\Enums\QuoteTypes;
use App\Repositories\BusinessQuoteRepository;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BusinessQuoteExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function collection()
    {
        return BusinessQuoteRepository::getData(QuoteTypes::CORPLINE->value, true);
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
            'LAST MODIFIED DATE',
            'PREMIUM',
            'NUMBER OF EMPLOYEES',
            'BUSINESS INSURANCE TYPE',
            'GENDER',
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
            date(config('constants.datetime_format'), strtotime($quote->updated_at)),
            $quote->premium,
            $quote->number_of_employees,
            optional($quote->businessTypeOfInsurance)->text,
            $quote->gender,
        ];
    }
}
