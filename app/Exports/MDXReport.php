<?php

namespace App\Exports;

use App\Models\EmbeddedProduct;
use App\Repositories\EmbeddedProductRepository;
use App\Traits\ExcelExportable;
use Maatwebsite\Excel\Concerns\FromCollection;

class MDXReport implements FromCollection
{
    use ExcelExportable;

    protected $embeddedProduct;
    protected $filters;

    public function __construct(EmbeddedProduct $embeddedProduct, $filters)
    {
        $this->embeddedProduct = $embeddedProduct;
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return EmbeddedProductRepository::getSoldTransactionList($this->embeddedProduct, $this->filters);
    }

    public function headings(): array
    {
        return [
            'EP REF-ID',
            'PAYMENT DATE',
            'PLAN COMMENCEMENT DATE',
            'PLAN END DATE',
            'CERTIFICATE NUMBER',
            'FULL NAME',
            'EMIRATES ID NUMBER',
            'DOB',
            'AGE',
            'VEHICLE',
            'CONTRIBUTION AMOUNT',
            'POLICY ISSUE STATUS',
        ];
    }

    public function map($certificate): array
    {
        return [
            $certificate['ref_id'],
            $certificate['payment_date'],
            $certificate['plan_start_date'],
            $certificate['plan_end_date'],
            $certificate['certificate_number'],
            $certificate['name'],
            $certificate['emirates_id_number'],
            $certificate['dob'],
            $certificate['age'],
            $certificate['vehicle'],
            $certificate['contribution_amount'],
            $certificate['status'],
        ];
    }
}
