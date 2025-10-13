<?php

namespace App\Exports;

use App\Services\CarQuoteService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class NonPUAQuoteExport implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison
{
    use Exportable;

    protected $nonPUALeads;
    protected $puaLeads;

    public function __construct($requestParams = [])
    {
        $this->nonPUALeads = app(CarQuoteService::class)->exportnonPUAAuthorized($requestParams);
        $this->puaLeads = app(CarQuoteService::class)->exportPUAAuthorized($requestParams);
    }

    public function collection()
    {
        $leads = $this->nonPUALeads[0];

        $nonPUALeadCounts = $this->nonPUALeads[0]->count();
        $puaLeadCounts = $this->puaLeads[0]->count();

        $teamCounts = $this->nonPUALeads[1];

        $exportData = collect();

        foreach ($leads as $lead) {
            $exportData->push($lead);
        }

        // ADD BlANK LINE
        $exportData->push((object) [' ' => ' ']);
        $exportData->push((object) [' ' => ' ']);
        $exportData->push((object) [' ' => ' ']);

        $exportData->push((object) [
            'NonPUA' => 'PUA: ',
            'Total' => $puaLeadCounts ?: '0',
        ]);
        $exportData->push((object) [
            'NonPUA' => 'Non-PUA: ',
            'Total' => $nonPUALeadCounts ?: '0',
        ]);
        // ADD BlANK LINE
        $exportData->push((object) [' ' => ' ']);
        $exportData->push((object) [' ' => ' ']);

        foreach ($teamCounts as $team) {
            $exportData->push((object) [
                'Team' => $team->Team,
                'Total' => $team->Total ?: '0',
            ]);
        }

        // Define all statusses
        $allStatuses = [
            'Payment Link Requested By Customer' => 0,
            'Payment Link In Progress' => 0,
            'Payment Link Sent To Customer' => 0,
        ];

        // Count leads by status
        foreach ($leads as $lead) {
            if (isset($allStatuses[$lead->leadstatus])) {
                $allStatuses[$lead->leadstatus]++;
            }
        }

        $exportData->push((object) [' ' => ' ']);
        $exportData->push((object) [' ' => ' ']);

        // Add status counts to export data
        foreach ($allStatuses as $status => $count) {
            $exportData->push((object) [
                'Quote Status' => $status,
                'Count' => $count,
            ]);
        }

        return $exportData;
    }

    public function headings(): array
    {
        return [
            'Ref-ID',
            'Premium Authorized',
            'Payment Auth Date',
            'Lead Status',
            'Payment Status',
            'Source',
            'Make',
            'Model',
            'Assigned Advisor Email',
        ];
    }

    public function map($quote): array
    {
        if (isset($quote->RefID)) {
            return [
                $quote->RefID,
                $quote->premiumauthorized,
                $quote->paymentauthdate ? date(config('constants.datetime_format'), strtotime($quote->paymentauthdate)) : '',
                $quote->leadstatus,
                $quote->paymentstatus,
                $quote->source,
                $quote->make,
                $quote->model,
                $quote->assignedadvisoremail,
            ];
        } elseif (isset($quote->NonPUA)) {
            return [
                $quote->NonPUA,
                $quote->Total,
            ];
        } elseif (isset($quote->Team)) {
            return [
                $quote->Team,
                $quote->Total ?? number_format(0),
            ];
        } elseif (isset($quote->{'Quote Status'})) {
            return [
                $quote->{'Quote Status'},
                $quote->{'Count'},
            ];
        }

        return array_fill(0, 11, '');
    }
}
