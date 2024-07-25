<?php

namespace App\Exports;

use App\Models\EmbeddedProduct;
use App\Repositories\EmbeddedProductRepository;
use App\Traits\ExcelExportable;

class EmbeddedProductReport
{
    use ExcelExportable;

    private $embeddedProduct;
    private $filters;

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
        $this->filters['excel_export'] = true;

        return EmbeddedProductRepository::getSoldTransactionList($this->embeddedProduct, $this->filters);
    }

    public function headings(): array
    {
        $isAlfredProtect = checkAlfredProtect($this->embeddedProduct->short_code);

        $defaultColumns = [
            'EP REF-ID',
            'ADVISOR NAME',
            'PAYMENT DATE',
            'PLAN COMMENCEMENT DATE',
            'PLAN END DATE',
            ...($isAlfredProtect ? ['Plan Type'] : []),
            'CERTIFICATE NUMBER',
            'FULL NAME',
            'EMIRATES ID NUMBER',
            'DOB',
            'AGE',
            'VEHICLE',
            'CONTRIBUTION AMOUNT',
            'POLICY ISSUE STATUS',
            ...($isAlfredProtect ? [
                'tax_invoice_no',
                'tax_invoice_buyer_no',
                'credit_note_no',
                'credit_note_buyer_no',
                'commission_with_vat',
                'commission_without_vat',
                'policy_price',
                'policy_status',
            ] : [])

        ];

        return $defaultColumns;
    }

    public function map($certificate): array
    {
        $isAlfredProtect = checkAlfredProtect($this->embeddedProduct->short_code);

        return [
            $certificate->ref_id,
            $certificate->advisor_name,
            $certificate->payment_date,
            $certificate->plan_start_date,
            $certificate->plan_end_date,
            ...($isAlfredProtect ? [$certificate->plan_type]: []),
            $certificate->certificate_number,
            $certificate->name,
            $certificate->emirates_id_number,
            $certificate->dob,
            $certificate->age,
            $certificate->vehicle,
            $certificate->contribution_amount,
            $certificate->status,
            ...($isAlfredProtect ? [
                $certificate->tax_invoice_no,
                $certificate->tax_invoice_buyer_no,
                $certificate->credit_note_no,
                $certificate->credit_note_buyer_no,
                $certificate->commission_with_vat,
                $certificate->commission_without_vat,
                $certificate->policy_price,
                $certificate->policy_status,
            ] : [])
        ];
    }
}
