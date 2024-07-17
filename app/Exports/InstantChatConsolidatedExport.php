<?php

namespace App\Exports;

use App\Traits\ExcelExportable;

class InstantChatConsolidatedExport
{
    use ExcelExportable;

    public function collection()
    {

    }

    public function headings(): array
    {
        return [
            'QUOTE TYPE',
            'REF ID',
            'DATE OF FIRST INTERACTION',
            'COMMUNICATION CHANNEL',
            'BATCH',
            'TRANSACTION TYPE',
            'SEGMENT',
            'NO. OF INTERACTIONS WITH IA',
            'COUNT OF FALLBACKS',
            'PAYMENT STATUS',
            'SALE LEADS',
            'PROVIDER NAME',
            'PLAN TYPE',
            'PLAN NAME',
            'PRICE',
            'PAYMENT DATE',
            'EP PURCHASED',
        ];
    }

    public function map($data): array
    {
        return [
            $data->quote_type,
            $data->ref_id,
            $data->date_of_first_interaction,
            $data->communication_cahnnel,
            $data->batch,
            $data->transaction_type,
            $data->segment,
            $data->no_of_intractions,
            $data->fallback,
            $data->payment_status,
            $data->sale_leads,
            $data->provider_name,
            $data->plan_type,
            $data->plan_name,
            $data->price,
            $data->payment_date,
            $data->ep_purchased,
        ];
    }
}
