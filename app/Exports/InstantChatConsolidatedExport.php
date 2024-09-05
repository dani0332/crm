<?php

namespace App\Exports;

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
            'QUOTE TYPE',
            'REF ID',
            'CREATED AT',
            'MESSAGE',
            'ROLE',
            'EMPLOYEE FLAG',
            'EMAIL ID',
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
            $chat->quote_id,
            $chat->created_at,
            $chat->msg,
            $chat->role,
            $chat->employee_flag,
            $chat->email,
            $chat->user_system,
            $chat->user_ip_address,
            $chat->communication_channel,
            $chat->input_tokens_usage,
            $chat->completion_tokens,
            $chat->total_tokens,
        ];
    }
}
