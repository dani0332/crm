<?php

namespace App\Exports;

use App\Contracts\CsvExportableInterface;
use App\Enums\QuoteStatusEnum;
use App\Services\InstantAlfredService;
use App\Traits\InstantChatChunkedExportable;
use App\Traits\ModernCsvExportable;

class InstantChatConsolidatedExport implements CsvExportableInterface
{
    use InstantChatChunkedExportable, ModernCsvExportable;

    public function collection(array $requestParams = []): \Illuminate\Support\Collection
    {
        // Merge export parameters with the current request to ensure date filters are applied
        if (! empty($requestParams)) {
            // Map export parameters to the format expected by InstantAlfredService
            $mappedParams = $this->mapExportParameters($requestParams);
            request()->merge($mappedParams);
        }

        return app(InstantAlfredService::class)->generateChatConsolidateReport();
    }

    /**
     * Get the query builder instance to use for chunking
     * Returns the base SQL query builder for chunked processing with MongoDB integration
     */
    public function getQuery(array $requestParams = []): \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|null
    {
        $instantAlfredService = app(InstantAlfredService::class);
        $query = $instantAlfredService->getChatConsolidateReportQuery($requestParams);

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
            'COMMUNICATION CHANNEL',
            'BATCH',
            'RENEWAL BATCH',
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
            'PAID DATE',
            'AUTHORISED DATE',
            'ADVISOR ASSIGNED DATE',
            'LEAD ASSIGNMENT TRIGGER',
            // 'PAID AT',
            // 'EP PURCHASED',
        ];
    }

    public function map($chat): array
    {
        return [
            $chat->quote_type ?? explode('-', $chat->code)[0], // 'QUOTE TYPE'
            $chat->code ?? 'N/A', // 'REF ID'
            $chat->lead_created_at ?? 'N/A', // 'LEAD CREATED AT'
            $chat->chat_initiated_at ?? $chat->date_of_first_interaction ?? 'N/A', // 'DATE OF FIRST INTERACTION'
            $this->formatCommunicationChannel($chat->communication_channels ?? []), // 'COMMUNICATION CHANNEL'
            $chat->quote_batch_id_text ?? 'N/A', // 'BATCH'
            $chat->renewal_batch_id_text ?? 'N/A', // 'RENEWAL BATCH'
            $chat->transaction_type_text ?? 'N/A', // 'TRANSACTION TYPE'
            $chat->segment ?? 'N/A', // 'SEGMENT'
            $chat->customer_interactions ?? 0, // 'NO. OF MESSAGES SENT BY CUSTOMER TO AI'
            $chat->ai_interactions ?? 0, // 'NO. OF RESPONSES SENT BY AI TO CUSTOMER'
            $chat->total_ai_interactions ?? 0, // 'TOTAL NO OF INTERACTIONS'
            isset($chat->fallbacks) && $chat->fallbacks === 0 ? 'N/A' : (isset($chat->fallbacks) ? (string) $chat->fallbacks : 'N/A'), // 'COUNT OF FALLBACKS'
            $chat->payment_status ?? 'N/A', // 'PAYMENT STATUS'
            in_array(
                $chat->quote_status_id ?? null,
                [QuoteStatusEnum::TransactionApproved, QuoteStatusEnum::PolicyIssued,
                    QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked]
            ) ? 'Yes' : 'No', // 'SALE LEADS'
            $chat->provider_name ?? 'N/A', // 'PROVIDER NAME'
            $chat->plan_type ?? 'N/A', // 'PLAN TYPE'
            $chat->plan_name ?? 'N/A', // 'PLAN NAME'
            $chat->total_price ?? 'N/A', // 'PRICE'
            $chat->payment_paid_at ?? 'N/A',
            $chat->paid_at ?? 'N/A',
            $chat->advisor_assigned_date ?? 'N/A',
            $chat->lead_assignment_trigger_text ?? 'N/A', // 'LEAD ASSIGNMENT TRIGGER'
            // $chat->display_name ?? 'N/A', // 'EP PURCHASED'
        ];
    }

    private function formatCommunicationChannel($channel)
    {
        if ($channel instanceof \MongoDB\Model\BSONDocument || $channel instanceof \MongoDB\Model\BSONArray) {
            $channel = $channel->getArrayCopy();
        }

        $channels = array_filter($channel, function ($item) {
            return is_string($item) && ! empty($item);
        });

        sort($channels); // Sort channels alphabetically

        return implode(', ', $channels);
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
            'quote_batch_id', 'renewal_batch_id', 'quote_status_id', 'payment_status_id',
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
     * Get export metadata for instant chat consolidated reports
     */
    public function getExportMetadata(array $requestParams = []): array
    {
        return [
            'exportClass' => static::class,
            'timestamp' => now()->toISOString(),
            'parameters' => $requestParams,
            'sourceTable' => 'instant_chat_logs',
            'exportType' => 'instant_chat_consolidated',
            'description' => 'Consolidated report of instant chat interactions',
        ];
    }
}
