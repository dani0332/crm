<?php

namespace App\Exports;

use App\Services\HealthQuoteService;
use App\Traits\ExcelExportable;

class RMQuotesExport
{
    use ExcelExportable;

    public function collection()
    {
        return app(HealthQuoteService::class)->exportRmLeads()->get();
    }

    public function headings(): array
    {
        return [
            'Ref-ID',
            'Transaction Approved At',
            'Advisor Name',
            'Advisor Email',
            'Lead Status',
            'Payment Status',
            'Created At',
            'Car Teams',
            'Health Teams',
            'Advisor Team Name',
        ];
    }

    public function map($quote): array
    {
        return [
            $quote->Ref_Id ?? 'N/A',
            $quote->Transaction_Approved_At ?? 'N/A',
            $quote->Advisor_Name ?? 'N/A',
            $quote->Advisor_Email ?? 'N/A',
            $quote->Lead_Status ?? 'N/A',
            $quote->Payment_Status ?? 'N/A',
            $quote->Created_At ?? 'N/A',
            $quote->CarTeams ?? 'N/A',
            $quote->HealthTeams ?? 'N/A',
            $quote->AdvisorTeamName ?? 'N/A',
        ];
    }
}
