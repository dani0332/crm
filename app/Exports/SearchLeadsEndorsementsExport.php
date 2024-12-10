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
        if (request()->list == 'leads') {
            $response = [
                $row->code,
                // ($row?->customer?->first_name.' '.$row?->customer?->last_name) ?? $this->notAvailable,
                $row->company_name,
                $row->quote_status ?? $this->notAvailable,
                //                $row?->quotePayments->first()?->total_price ?? $this->notAvailable,
                $row->policy_number ?? $this->notAvailable,
                //                $row?->quotePayments->first()?->insuranceProvider?->text ?? $this->notAvailable,
                $row->quote_type ?? $this->notAvailable,
                //                $row?->advisor?->name ?? $this->notAvailable,
            ];
        } else {
            $response = [
                $row->code,
                //                ($row?->personalQuote?->customer?->first_name.' '.$row?->personalQuote?->customer?->last_name) ?? $this->notAvailable,
                $row->company_name,
                $row->status ?? $this->notAvailable,
                //                $row?->sendUpdatePayments?->first()?->total_price ?? $this->notAvailable,
                $row->policy_number ?? $this->notAvailable,
                //                $row?->insuranceProvider?->text ?? $this->notAvailable,
                $row->quote_type ?? $this->notAvailable,
                //                $row?->personalQuote?->advisor?->name ?? $this->notAvailable,
            ];
        }

        return $response;
    }

    public function title(): string
    {
        return request()->list == 'endorsements' ? 'Send Update List' : 'Leads List';
    }
}
