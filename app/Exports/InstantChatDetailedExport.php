<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InstantChatDetailedExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    protected $chat;
    public function __construct($chat)
    {
        $this->chat = $chat;
    }

    public function collection()
    {
        return $this->chat;
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
            'NO. OF MESSAGES SENT BY CUSTOMER TO AI',
            'NO. OF RESPONSES SENT BY AI TO CUSTOMER',
            'TOTAL NO OF INTERACTIONS',
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
            $data->quote_id,
            $data->created_at,
            $data->communication_channel,
            $data->batch,
            $data->transaction_type,
            $data->segment,
            $data->customer_interactions,
            $data->ai_interactions,
            $data->total_ai_interactions,
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
