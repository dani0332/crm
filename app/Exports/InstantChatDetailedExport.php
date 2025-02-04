<?php

namespace App\Exports;

use App\Services\InstantAlfredService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InstantChatDetailedExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    public function collection()
    {
        return app(InstantAlfredService::class)->generateChatDetailedReport();
    }

    public function headings(): array
    {
        return [
            'QUOTE TYPE',
            'REF ID',
            'CREATED AT',
            'MESSAGE',
            'ROLE',
            'EMPLOYEE FLAG',
            // 'EMAIL ID',
            'USER SYSTEM',
            'USER IP ADDRESS',
            'COMMUNICATION CHANNEL',
            'INPUT TOKENS USAGE',
            'OUTPUT TOKENS USAGE',
            'TOTAL TOKENS USED',
        ];
    }

    public function map($chat): array
    {
        return [
            $chat->quote_type,
            $chat->quote_id = strtoupper(substr($chat->quote_type, 0, 3)).'-'.$chat->quote_id,
            $chat->created_at,
            $chat->msg,
            $chat->role,
            isset($chat->employee_flag) ? $chat->employee_flag : 'N/A',
            // isset($chat->email) ? $chat->email : 'N/A',
            isset($chat->user_system) ? $chat->user_system : 'N/A',
            isset($chat->user_ip_address) ? $chat->user_ip_address : 'N/A',
            $this->formatCommunicationChannel($chat->communication_channel),
            isset($chat->input_tokens_usage) ? $chat->input_tokens_usage : 'N/A',
            isset($chat->completion_tokens) ? $chat->completion_tokens : 'N/A',
            isset($chat->total_tokens) ? $chat->total_tokens : 'N/A',
        ];
    }

    private function formatCommunicationChannel($channel)
    {
        // Check if the communication_channel is a BSONDocument
        if ($channel instanceof \MongoDB\Model\BSONDocument) {
            // Convert the BSONDocument to an array and return a formatted string
            return json_encode($channel->getArrayCopy());
        }

        // If it's not a BSONDocument, return it as-is (assuming it's already a string or null)
        return $channel ?? 'N/A';
    }
}
