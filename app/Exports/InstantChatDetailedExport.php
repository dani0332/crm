<?php

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Services\InstantAlfredService;
use App\Traits\InstantChatDetailedChunkedExportable;
use App\Traits\ModernCsvExportable;

class InstantChatDetailedExport implements CsvExportableInterface
{
    use InstantChatDetailedChunkedExportable, ModernCsvExportable;

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
     * Returns the base SQL query builder for chunked processing with MongoDB integration
     */
    public function getQuery(array $requestParams = []): \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|null
    {
        $instantAlfredService = app(InstantAlfredService::class);
        $query = $instantAlfredService->getChatDetailedReportQuery($requestParams);

        // The InstantAlfredService returns a Query\Builder, but our interface expects Eloquent\Builder
        // Since we have a custom processChunkedQuery method, this type mismatch is handled there
        // @phpstan-ignore-next-line
        return $query;
    }

    public function headings(): array
    {
        return [
            'QUOTE TYPE',
            'REF ID',
            'LEAD CREATED DATE',
            'DATE OF FIRST INTERACTION',
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
        // Handle both array (from MongoDB) and object data structures
        $quoteType = is_array($chat) ? ($chat['quote_type'] ?? 'N/A') : ($chat->quote_type ?? 'N/A');
        $quoteId = is_array($chat) ? ($chat['quote_id'] ?? 'N/A') : ($chat->quote_id ?? 'N/A');
        $leadCreatedDate = is_array($chat) ? ($chat['lead_created_at'] ?? 'N/A') : ($chat->lead_created_at ?? 'N/A');
        $createdAt = is_array($chat) ? ($chat['created_at'] ?? 'N/A') : ($chat->created_at ?? 'N/A');
        $msg = is_array($chat) ? ($chat['msg'] ?? 'N/A') : ($chat->msg ?? 'N/A');
        $role = is_array($chat) ? ($chat['role'] ?? 'N/A') : ($chat->role ?? 'N/A');

        return [
            $quoteType,
            strtoupper(substr($quoteType, 0, 3)).'-'.$quoteId,
            $leadCreatedDate,
            $createdAt,
            $msg,
            $role,
            is_array($chat) ? ($chat['employee_flag'] ?? 'N/A') : (isset($chat->employee_flag) ? $chat->employee_flag : 'N/A'),
            // is_array($chat) ? ($chat['email'] ?? 'N/A') : (isset($chat->email) ? $chat->email : 'N/A'),
            is_array($chat) ? ($chat['user_system'] ?? 'N/A') : (isset($chat->user_system) ? $chat->user_system : 'N/A'),
            is_array($chat) ? ($chat['user_ip_address'] ?? 'N/A') : (isset($chat->user_ip_address) ? $chat->user_ip_address : 'N/A'),
            $this->formatCommunicationChannel(
                is_array($chat) ? ($chat['communication_channel'] ?? null) : ($chat->communication_channel ?? null)
            ),
            is_array($chat) ? ($chat['input_tokens_usage'] ?? 'N/A') : (isset($chat->input_tokens_usage) ? $chat->input_tokens_usage : 'N/A'),
            is_array($chat) ? ($chat['completion_tokens'] ?? 'N/A') : (isset($chat->completion_tokens) ? $chat->completion_tokens : 'N/A'),
            is_array($chat) ? ($chat['total_tokens'] ?? 'N/A') : (isset($chat->total_tokens) ? $chat->total_tokens : 'N/A'),
            is_array($chat) ? ($chat['segment'] ?? 'N/A') : ($chat->segment ?? 'N/A'),
            is_array($chat) ? ($chat['lead_assignment_trigger_text'] ?? 'N/A') : ($chat->lead_assignment_trigger_text ?? 'N/A'),
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
