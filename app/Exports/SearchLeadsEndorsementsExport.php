<?php

namespace App\Exports;

use App\Exports\Reports\BaseReportsExport;
use Maatwebsite\Excel\Concerns\WithTitle;

class SearchLeadsEndorsementsExport extends BaseReportsExport implements WithTitle
{
    private string $notAvailable = 'N/A';
    public function headings(): array
    {
        if (isset(request()->list)) {
            return [
                request()->list == 'leads' ? 'REF-ID' : 'SU REF-ID',
                'CUSTOMER NAME',
                'COMPANY',
                'LEAD STATUS',
                'TOTAL PRICE',
                'POLICY NUMBER',
                'CURRENTLY INSURED WITH',
                'LINE OF BUSINESS',
                'ADVISOR',
            ];
        } else {
            return abort(404);
        }
    }

    public function map($row): array
    {
        $customerName = $this->notAvailable;
        if (isset($row?->customer_first_name) || isset($row?->customer_last_name)) {
            $customerName = ($row?->customer_first_name ?? null).' '.($row?->customer_last_name ?? null);
        }

        return [
            $row->code,
            $customerName,
            $row->company_name ?? $this->notAvailable,
            ((request()->list == 'leads') ? $row->quote_status : $row->status) ?? $this->notAvailable,
            $row->total_price ?? $this->notAvailable,
            $row->policy_number ?? $this->notAvailable,
            $row->insurance_provider ?? $this->notAvailable,
            $row->quote_type ?? $this->notAvailable,
            $row->advisor_name ?? $this->notAvailable,
        ];
    }

    public function title(): string
    {
        return request()->list == 'endorsements' ? 'Send Update List' : 'Leads List';
    }
}
