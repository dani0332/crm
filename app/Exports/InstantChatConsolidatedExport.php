<?php

namespace App\Exports;

use App\Enums\QuoteStatusEnum;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InstantChatConsolidatedExport implements FromArray, WithHeadings, WithMapping
{
    use Exportable;

    protected $chat;
    public function __construct(array $data)
    {
        $this->chat = [$data];
    }

    public function array(): array
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

    public function map($chat): array
    {
        return [
            $chat['quote_type'] ?? 'N/A', // 'QUOTE TYPE'
            $chat['code'] ?? 'N/A', // 'REF ID'
            $chat['date_of_first_interaction'] ?? $chat['chat_initiated_at'] ?? 'N/A', // 'DATE OF FIRST INTERACTION'
            $this->formatCommunicationChannel($chat['communication_channels'] ?? []), // 'COMMUNICATION CHANNEL'
            $chat['quote_batch_id_text'] ?? 'N/A', // 'BATCH'
            $chat['transaction_type_text'] ?? 'N/A', // 'TRANSACTION TYPE'
            $chat['segment'] ?? 'N/A', // 'SEGMENT'
            $chat['customer_interactions'] ?? 0, // 'NO. OF MESSAGES SENT BY CUSTOMER TO AI'
            $chat['ai_interactions'] ?? 0, // 'NO. OF RESPONSES SENT BY AI TO CUSTOMER'
            $chat['total_ai_interactions'] ?? 0, // 'TOTAL NO OF INTERACTIONS'
            $chat['fallbacks'] === 0 ? 'N/A' : $chat['fallbacks'], // 'COUNT OF FALLBACKS'
            $chat['payment_status_id_text'] ?? 'N/A', // 'PAYMENT STATUS'
            in_array(
                $chat['quote_status_id'] ?? null,
                [QuoteStatusEnum::TransactionApproved, QuoteStatusEnum::PolicyIssued,
                    QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked]
            ) ? 'Yes' : 'No', // 'SALE LEADS'
            $chat['provider_name'] ?? 'N/A', // 'PROVIDER NAME'
            $chat['plan_type_text'] ?? 'N/A', // 'PLAN TYPE'
            $chat['plan_name'] ?? 'N/A', // 'PLAN NAME'
            $chat['price'] ?? 'N/A', // 'PRICE'
            $chat['payment_status_id_created_at'] ?? 'N/A', // 'PAYMENT DATE'
            $chat['display_name'] ?? 'N/A', // 'EP PURCHASED'
        ];
    }

    private function formatCommunicationChannel($channel)
    {

        if ($channel instanceof \MongoDB\Model\BSONArray) {
            $channel = $channel->getArrayCopy();
        }

        return implode(', ', $channel) ?? 'N/A';
    }
}
