<?php

namespace App\Exports;

use App\Enums\QuoteTypeId;
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
                ($row?->customer?->first_name.' '.$row?->customer?->last_name) ?? $this->notAvailable,
                $this->getCompanyName(request()->list, $row),
                $row?->quoteStatus?->text ?? $this->notAvailable,
                $this->resolveNumberFormat($row?->quotePayments->first()?->total_price ?? 0) ?? $this->notAvailable,
                $row->policy_number ?? $this->notAvailable,
                $row?->quotePayments->first()?->insuranceProvider?->text ?? $this->notAvailable,
                $row?->quoteType?->code ?? $this->notAvailable,
                $row?->advisor?->name ?? $this->notAvailable,
            ];
        } else {
            $response = [
                $row->code,
                ($row?->personalQuote?->customer?->first_name.' '.$row?->personalQuote?->customer?->last_name) ?? $this->notAvailable,
                $this->getCompanyName(request()->list, $row),
                $row->status ?? $this->notAvailable,
                $this->resolveNumberFormat($row?->sendUpdatePayments?->first()?->total_price ?? 0) ?? $this->notAvailable,
                $row?->personalQuote?->policy_number ?? $this->notAvailable,
                $row?->insuranceProvider?->text ?? $this->notAvailable,
                $row?->quoteType?->code ?? $this->notAvailable,
                $row?->personalQuote?->advisor?->name ?? $this->notAvailable,
            ];
        }

        return $response;
    }

    private function getCompanyName($exportType, $row): string
    {
        $personalLOBs = [QuoteTypeId::Bike, QuoteTypeId::Yacht, QuoteTypeId::Pet, QuoteTypeId::Cycle, QuoteTypeId::Jetski];
        $nonEcomLOBs = [
            QuoteTypeId::Car => 'carQuoteRequest',
            QuoteTypeId::Home => 'homeQuoteRequest',
            QuoteTypeId::Health => 'healthQuoteRequest',
            QuoteTypeId::Life => 'lifeQuoteRequest',
            QuoteTypeId::Business => 'BusinessQuoteRequest',
            QuoteTypeId::Travel => 'TravelQuoteRequest',
            QuoteTypeId::GroupMedical => 'BusinessQuoteRequest',
            QuoteTypeId::Corpline => 'BusinessQuoteRequest',
        ];

        $isPersonalLOB = in_array($row->quote_type_id, $personalLOBs);
        $rowObject = $exportType == 'leads' ? $row : $row?->personalQuote;

        return $this->getCompanyNameFromRow($rowObject, $isPersonalLOB, $nonEcomLOBs) ?? $this->notAvailable;
    }

    private function getCompanyNameFromRow($row, $isPersonalLOB, $nonEcomLOBs): string
    {
        if ($isPersonalLOB) {
            return $row?->quoteRequestEntityMapping?->entity?->company_name ?? $this->notAvailable;
        }

        return $row?->{$nonEcomLOBs[$row->quote_type_id]}?->quoteRequestEntityMapping?->entity?->company_name ?? $this->notAvailable;
    }

    public function title(): string
    {
        return request()->list == 'endorsements' ? 'Send Update List' : 'Leads List';
    }
}
