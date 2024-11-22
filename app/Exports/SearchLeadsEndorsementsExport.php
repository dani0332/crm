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
        $leadCustomerName = $row?->customer?->first_name.' '.$row?->customer?->last_name;
        $endorsementCustomerName = $row?->personalQuote?->customer?->first_name.' '.$row?->personalQuote?->customer?->last_name;
        $totalPrice = (request()->list == 'leads' ? $row?->quotePayments?->first()?->total_price : $row->personalQuote?->first()?->quotePayments?->first()?->total_price);

        return [
            $row->code ?? $this->notAvailable,
            (request()->list == 'leads' ? $leadCustomerName : $endorsementCustomerName) ?? $this->notAvailable,
            (request()->list == 'leads' ? $row->quoteRequestEntityMapping?->first()?->entity?->company_name : $row->personalQuote?->first()?->quoteRequestEntityMapping?->first()?->entity?->company_name) ?? $this->notAvailable,
            (request()->list == 'leads' ? $row->quoteStatus?->text : $row->status) ?? $this->notAvailable,
            $totalPrice ? $this->resolveNumberFormat($totalPrice) : $this->notAvailable,
            (request()->list == 'leads' ? $row->policy_number : $row->personalQuote?->policy_number) ?? $this->notAvailable,
            (request()->list == 'leads' ? $row?->quotePayments?->first()?->insurance_provider_id : $row->insuranceProvider?->text) ?? $this->notAvailable,
            $row->quoteType?->code ?? $this->notAvailable,
            (request()->list == 'leads' ? $row->advisor?->name : $row->personalQuote?->advisor?->name) ?? $this->notAvailable,
        ];
    }

    public function title(): string
    {
        return request()->list == 'endorsements' ? 'Send Update List' : 'Leads List';
    }
}
