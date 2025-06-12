<?php

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Services\InstantAlfredService;
use App\Traits\ModernCsvExportable;

class InstantChatDetailedExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    public function collection(array $requestParams = []): \Illuminate\Support\Collection
    {
        // Merge export parameters with the current request to ensure date filters are applied
        if (! empty($requestParams)) {
            // Map export parameters to the format expected by InstantAlfredService
            $mappedParams = $this->mapExportParameters($requestParams);
            request()->merge($mappedParams);
        }

        return app(InstantAlfredService::class)->generateChatDetailedReport();
    }

    /**
     * Get the query builder instance to use for chunking
     * Note: InstantAlfredService doesn't support query builders yet, so this returns null
     * for now, falling back to collection() method
     */
    public function getQuery(array $requestParams = []): \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|null
    {
        // Future enhancement: Could implement chunked processing if InstantAlfredService supports it
        return null;
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
            'SEGMENT',
            'LEAD ASSIGNMENT TRIGGER',
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
            $chat->segment ?? 'N/A',
            $chat->lead_assignment_trigger_text ?? 'N/A',
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

    /**
     * Map export parameters to the format expected by InstantAlfredService
     */
    private function mapExportParameters(array $requestParams): array
    {
        $mappedParams = [];

        // Map date parameters - InstantAlfredService expects 'chat_initiated_at' as an array
        if (isset($requestParams['created_at_start']) && isset($requestParams['created_at_end'])) {
            $mappedParams['chat_initiated_at'] = [
                $requestParams['created_at_start'],
                $requestParams['created_at_end'],
            ];
        }

        // Map other common parameters
        if (isset($requestParams['quoteType'])) {
            $mappedParams['quoteType'] = $requestParams['quoteType'];
        }

        if (isset($requestParams['report'])) {
            $mappedParams['report'] = $requestParams['report'];
        }

        if (isset($requestParams['sortType'])) {
            $mappedParams['sortType'] = $requestParams['sortType'];
        }

        // Pass through any other parameters that might be relevant
        $passThroughParams = [
            'quoteId', 'email', 'mobile_no', 'transaction_type_id',
            'quote_batch_id', 'quote_status_id', 'payment_status_id',
            'assigment_type', 'sale_leads', 'segment',
        ];

        foreach ($passThroughParams as $param) {
            if (isset($requestParams[$param])) {
                $mappedParams[$param] = $requestParams[$param];
            }
        }

        return $mappedParams;
    }

    /**
     * Get export metadata for instant chat detailed reports
     */
    public function getExportMetadata(array $requestParams = []): array
    {
        return [
            'exportClass' => static::class,
            'timestamp' => now()->toISOString(),
            'parameters' => $requestParams,
            'sourceTable' => 'instant_chat_logs',
            'exportType' => 'instant_chat_detailed',
            'description' => 'Detailed report of instant chat interactions and messages',
        ];
    }
}
