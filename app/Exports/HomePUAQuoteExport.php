<?php

namespace App\Exports;

use App\Repositories\HomeQuoteRepository;
use App\Services\Logger\LoggerService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class HomePUAQuoteExport implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison
{
    use Exportable;

    protected $data;

    public function __construct($requestParams = [])
    {
        LoggerService::info('HomePUAQuoteExport initialized');
        $this->data = app(HomeQuoteRepository::class)->exportPUAAuthorized($requestParams);
    }

    public function collection()
    {
        $leads = $this->data[0];

        $teamCounts = $this->data[1];

        $exportData = collect();

        foreach ($leads as $lead) {
            $exportData->push($lead);
        }

        if ($teamCounts->isNotEmpty()) {
            $exportData->push((object) [' ' => ' ']);
            $exportData->push((object) [' ' => ' ']);
            $exportData->push((object) [' ' => ' ']);
            $exportData->push((object) ['Teams' => '']);
            $exportData->push((object) ['Total' => '']);
        }

        foreach ($teamCounts as $team) {
            $exportData->push((object) [
                'Team' => $team->Team,
                'Total' => $team->Total,
            ]);
        }

        // Define all possible payment statuses for home insurance
        $allStatuses = [
            'Payment Link Requested By Customer' => 0,
            'Payment Link In Progress' => 0,
            'Payment Link Sent To Customer' => 0,
        ];

        // Count leads by status
        foreach ($leads as $lead) {
            $leadStatus = $lead->quoteStatus->text ?? '';
            if (isset($allStatuses[$leadStatus])) {
                $allStatuses[$leadStatus]++;
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
            'Ownership Status',
            'Type of Property',
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
                $quote->quoteStatus->text ?? '',
                $quote->paymentstatus,
                $quote->source,
                $quote->homeQuote?->lookupPossessionType?->text ?? 'N/A',
                $quote->homeQuote?->lookupAccommodationType?->text ?? 'N/A',
                $quote->advisor?->email ?? '',
            ];
        } elseif (isset($quote->Team)) {
            return [
                $quote->Team,
                $quote->Total ?? number_format(0),
            ];
        } elseif (isset($quote->{'Teams'})) {
            return [
                'Teams',
                'Total Count',
            ];
        } elseif (isset($quote->{'Quote Status'})) {
            return [
                $quote->{'Quote Status'},
                $quote->{'Count'},
            ];
        }

        return array_fill(0, 9, '');
    }
}
