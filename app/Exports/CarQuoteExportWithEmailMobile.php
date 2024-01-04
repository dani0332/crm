<?php

namespace App\Exports;

use App\Services\CarQuoteService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CarQuoteExportWithEmailMobile implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function collection()
    {
        return app(CarQuoteService::class)->getExportDataWithMobileAndEmail();
    }

    public function headings(): array
    {
        return [
            'Ref ID',
            'Batch No',
            'First Name',
            'Last Name',
            'Email',
            'Mobile No',
            'Created Date',
            'Lead Status',
            'Tier',
            'Assigned To',
        ];
    }

    public function map($quote): array
    {

        return [
            $quote->code,
            $quote->batch_no,
            $quote->first_name,
            $quote->last_name,
            $quote->email,
            $quote->mobile_no,
            date('d-m-Y H:i:s', strtotime($quote->created_at)),
            $quote->status,
            $quote->tier,
            $quote->assigned_to,
        ];
    }
}
