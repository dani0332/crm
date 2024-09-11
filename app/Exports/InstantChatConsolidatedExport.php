<?php

namespace App\Exports;

use App\Enums\QuoteStatusEnum;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InstantChatConsolidatedExport implements FromCollection, WithHeadings, WithMapping
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
            $data->code,
            $data->chat_initiated_at,
            $data->chat['communication_channel'],
            $data->quote_batch_id_text,
            $data->transaction_type_text,
            // $data->segment,
            $data->chat['user_count'],
            $data->chat['ai_count'],
            $data->chat['user_count'] + $data->chat['ai_count'],
            $data->chat['fallback_count'],
            $data->payment_status_id_text,
            in_array(
                $data->quote_status_id,
                [QuoteStatusEnum::TransactionApproved, QuoteStatusEnum::PolicyIssued,
                    QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked],
            ) ? 'Yes' : 'No',
            $data->car_plan_provider_id_text,
            // $data->plan_type,
            $data->plan_id_text,
            // $data->price,
            $data->payment_status_id_created_at,
            // $data->ep_purchased,
        ];
    }
}
