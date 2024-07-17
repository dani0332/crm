<?php

namespace App\Exports;

use App\Traits\ExcelExportable;

class InstantChatDetailedExport
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

    public function map($data): array
    {
        return [
            $data->quote_type,
            $data->ref_id,
            $data->date_of_first_interaction,
            $data->message,
            $data->role,
            $data->employee_flag,
            $data->email,
            $data->user_system,
            $data->user_ip_address,
            $data->communication_channel,
            $data->input_tokens_usage,
            $data->output_tokens_usage,
            $data->total_tokens_used,
        ];
    }
}
