<?php

namespace App\Exports;

use App\Repositories\LifeQuoteRepository;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LifeQuotesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    public function collection()
    {
        return LifeQuoteRepository::exportData();
    }

    public function headings(): array
    {
        return [
            'Ref ID',
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
            'IS ECOMMERCE',
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
            date('d-m-Y H:i:s', strtotime($quote->created_at)),
            date('d-m-Y H:i:s', strtotime($quote->updated_at)),
            $quote->transapp_code,
            $quote->premium,
            $quote->policy_number,
            $quote->source,
            $quote->is_ecommerce ? 'Yes' : 'No',
        ];
    }
}
