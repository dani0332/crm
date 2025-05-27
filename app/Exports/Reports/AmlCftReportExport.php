<?php

declare(strict_types=1);

namespace App\Exports\Reports;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AmlCftReportExport implements FromCollection, WithHeadings, Responsable
{
    /**
     * @var Collection
     */
    protected Collection $data;

    /**
     * Create a new export instance.
     *
     * @param Collection $data
     */
    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'No.',
            'Ref-ID',
            'Customer Full Name',
            'Customer EID No/Trade license',
            'Customer Type',
            'Resident/Non-Resident customer',
            'Customer Risk Profile (High /Medium/Low)',
            'Insurance Policy Number',
            'Type of insurance Policy',
            'Number of Transactions',
            'Transaction Amount',
            'Name of the Insurance Company',
            'Policy Start Date',
            'Policy End Date',
            'Lead status',
            'Whether PEP Customer or Not',
            'Date of last AML screening',
            'Remarks',
            
        ];
    }

    public function map($item): array
    {
        static $row = 1;
        $kyc = $item->insuredKyc;
        return [
            $row++,
            $item->uuid ?? '',
            trim(($item->first_name ?? '') . ' ' . ($item->last_name ?? '')),
            $item->id_number ?? '',
            $item->customer_type ?? '',
            $kyc->residency_status ?? '',
            $kyc->risk_profile ?? '',
            $kyc->policy_number ?? '',
            $kyc->policy_type ?? '',
            $kyc->transaction_count ?? '',
            $kyc->transaction_amount ?? '',
            $kyc->insurance_company ?? '',
            $kyc->policy_start_date ?? '',
            $kyc->policy_end_date ?? '',
            $kyc->lead_status ?? '',
            $kyc->pep ?? '',
            $kyc->last_aml_screening_date ?? '',
            $kyc->remarks ?? '',
        ];
    }
    
    public function toResponse($request)
    {
        $fileName = 'AML_CTF_Report_' . now()->format('Ymd_His') . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download($this, $fileName);
    }
} 