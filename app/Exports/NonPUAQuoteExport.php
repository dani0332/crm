<?php

namespace App\Exports;

use App\Services\CarQuoteService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class NonPUAQuoteExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    protected $nonPUALeads;
    protected $puaLeads;

    public function __construct()
    {
        $this->nonPUALeads = app(CarQuoteService::class)->exportnonPUAAuthorized();
        $this->puaLeads = app(CarQuoteService::class)->exportPUAAuthorized();
    }

    public function collection()
    {
        $leads = $this->nonPUALeads[0];

        $nonPUALeadCounts = $this->nonPUALeads[0]->count();
        $puaLeadCounts = $this->puaLeads[0]->count();

        $teamCounts = $this->nonPUALeads[1];

        $countByStatus = $this->nonPUALeads[2];

        $exportData = collect();

        foreach ($leads as $lead) {
            $exportData->push($lead);
        }

        // ADD BlANK LINE
        if ($teamCounts->isNotEmpty()) {
            $exportData->push((object) [' ' => ' ']);
            $exportData->push((object) [' ' => ' ']);
            $exportData->push((object) [' ' => ' ']);
        }

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

        if ($countByStatus->isNotEmpty()) {
            $exportData->push((object) [' ' => ' ']);
            $exportData->push((object) [' ' => ' ']);
        }

        foreach ($countByStatus as $status) {
            $exportData->push((object) [
                'Quote Status' => $status->text,
                'Count' => $status->count,
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
