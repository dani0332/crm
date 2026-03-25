<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InstantChatReportsEnum;
use App\Enums\LeadAssignmentTriggerEnum;
use App\Enums\QuoteStatusEnum;
use App\Jobs\DeleteTempOCBPDFFileJob;
use App\Models\AlfredChat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;

class InstantAlfredExportService
{
    private const AZURE_DISK = 'azureIMPrivate';
    private const URL_EXPIRY_HOURS = 1;
    private const BATCH_SIZE = 500;
    private const FLUSH_INTERVAL = 1000;
    private const UUID_BATCH_SIZE = 2000;

    public function generateCsvAndGetUrl(array $params): array
    {
        $reportType = $params['report'] ?? InstantChatReportsEnum::DETAILED_REPORT;

        if ($reportType === InstantChatReportsEnum::CONSOLIDATED_REPORT) {
            $result = $this->generateConsolidatedReportCsv($params);
        } else {
            $result = $this->generateDetailedReportCsv($params);
        }

        return [
            'success' => true,
            'download_url' => $result['url'],
            'records' => (string) $result['records'],
            'report_type' => $reportType,
            'time_period' => $this->formatTimePeriod($params),
        ];
    }

    private function generateDetailedReportCsv(array $params): array
    {
        $sqlData = $this->fetchDetailedReportSqlData($params);
        $uuids = array_keys($sqlData);

        $fileName = 'detailed_report_'.now()->format('Y-m-d_His').'_'.uniqid().'.csv';
        $azurePath = "temp/exports/{$fileName}";

        $stream = fopen('php://temp/maxmemory:10485760', 'r+');
        $records = 0;

        try {
            fputcsv($stream, $this->getDetailedReportHeaders());

            if (! empty($uuids)) {
                foreach ($this->streamDetailedReportMongoData($uuids, $sqlData, $params) as $row) {
                    fputcsv($stream, $row);
                    $records++;

                    if ($records % self::FLUSH_INTERVAL === 0) {
                        gc_collect_cycles();
                    }
                }
            }

            $result = $this->uploadCsvToAzure($stream, $azurePath);
            $result['records'] = $records;

            return $result;

        } catch (\Throwable $e) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            throw $e;
        }
    }

    private function fetchDetailedReportSqlData(array $params): array
    {
        DB::setDefaultConnection('mysql_read');

        try {
            $reportService = app(InstantAlfredReportService::class);
            $hasSortType = ! empty($params['sortType']);

            if ($hasSortType) {
                $sortedIds = $reportService->getSortedIds($params);
                $rows = collect();

                foreach (array_chunk($sortedIds, self::UUID_BATCH_SIZE) as $idBatch) {
                    $rows = $rows->merge($reportService->getReportQueryByIds($idBatch, $params)->get());
                }
            } else {
                $rows = $reportService->getReportQuery($params)->get();
            }

            $sqlData = $rows
                ->groupBy('uuid')
                ->map(fn ($group) => $group->sortBy('id')->first())
                ->map(fn ($item) => (array) $item)
                ->toArray();

            return $reportService->enrichDetailedSqlData($sqlData, $params);
        } finally {
            DB::setDefaultConnection('mysql');
        }
    }

    private function streamDetailedReportMongoData(array $uuids, array $sqlData, array $params): \Generator
    {
        foreach (array_chunk($uuids, self::UUID_BATCH_SIZE) as $uuidBatch) {
            $pipeline = $this->buildDetailedReportPipeline($uuidBatch, $params);

            $cursor = AlfredChat::raw(fn ($collection) => $collection->aggregate($pipeline, [
                'allowDiskUse' => true,
                'cursor' => ['batchSize' => self::BATCH_SIZE],
            ]));

            foreach ($cursor as $doc) {
                yield $this->mapDetailedReportRow($doc, $sqlData);
            }
        }
    }

    private function buildDetailedReportPipeline(array $uuids, array $params): array
    {
        $matchConditions = ['quote_id' => ['$in' => $uuids]];

        $sortDir = ($params['sortType'] ?? 'asc') === 'desc' ? -1 : 1;

        return [
            ['$match' => $matchConditions],
            ['$project' => [
                'created_at' => 1,
                'role' => 1,
                'msg' => 1,
                'quote_id' => 1,
                'quote_type' => $params['quoteType'] ?? 'Car',
                'employee_flag' => '$who_chatted.is_employee',
                'email' => '$who_chatted.email',
                'user_system' => '$who_chatted.user_agent',
                'user_ip_address' => '$who_chatted.ip',
                'communication_channel' => '$channel',
                'input_tokens_usage' => '$response.usage.prompt_tokens',
                'completion_tokens' => '$response.usage.completion_tokens',
                'total_tokens' => '$response.usage.total_tokens',
            ]],
            ['$sort' => ['created_at' => $sortDir]],
        ];
    }

    private function getDetailedReportHeaders(): array
    {
        return [
            'Quote ID',
            'Quote Type',
            'Segment',
            'Lead Created',
            'Renewal Batch',
            'Assignment Trigger',
            'Chat Date',
            'Role',
            'Message',
            'Channel',
            'Employee',
            'Email',
            'User System',
            'User IP Address',
            'Input Tokens',
            'Completion Tokens',
            'Total Tokens',
        ];
    }

    private function mapDetailedReportRow($doc, array $sqlData): array
    {
        $quoteId = $doc['quote_id'] ?? '';
        $sql = $sqlData[$quoteId] ?? [];

        return [
            $quoteId,
            $doc['quote_type'] ?? 'N/A',
            $sql['segment'] ?? 'N/A',
            $sql['lead_created_at'] ?? 'N/A',
            $sql['renewal_batch_text'] ?? 'N/A',
            isset($sql['lead_assignment_trigger'])
                ? LeadAssignmentTriggerEnum::getAssignmentTypeText($sql['lead_assignment_trigger'])
                : 'N/A',
            $doc['created_at'] ?? 'N/A',
            $doc['role'] ?? 'N/A',
            $doc['msg'] ?? '',
            $this->formatCommunicationChannel($doc['communication_channel'] ?? null),
            $doc['employee_flag'] ?? 'N/A',
            $doc['email'] ?? 'N/A',
            $doc['user_system'] ?? 'N/A',
            $doc['user_ip_address'] ?? 'N/A',
            $doc['input_tokens_usage'] ?? 0,
            $doc['completion_tokens'] ?? 0,
            $doc['total_tokens'] ?? 0,
        ];
    }

    private function generateConsolidatedReportCsv(array $params): array
    {
        $fileName = 'consolidated_report_'.now()->format('Y-m-d_His').'_'.uniqid().'.csv';
        $azurePath = "temp/exports/{$fileName}";

        $stream = fopen('php://temp/maxmemory:10485760', 'r+');
        $records = 0;

        try {
            fputcsv($stream, $this->getConsolidatedReportHeaders());

            DB::setDefaultConnection('mysql_read');
            try {
                foreach ($this->streamConsolidatedReportData($params) as $row) {
                    fputcsv($stream, $row);
                    $records++;

                    if ($records % self::FLUSH_INTERVAL === 0) {
                        gc_collect_cycles();
                    }
                }
            } finally {
                DB::setDefaultConnection('mysql');
            }

            $result = $this->uploadCsvToAzure($stream, $azurePath);
            $result['records'] = $records;

            return $result;

        } catch (\Throwable $e) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            throw $e;
        }
    }

    private function streamConsolidatedReportData(array $params): \Generator
    {
        $reportService = app(InstantAlfredReportService::class);
        $hasSortType = ! empty($params['sortType']);

        if ($hasSortType) {
            $sortedIds = $reportService->getSortedIds($params);

            foreach (array_chunk($sortedIds, self::UUID_BATCH_SIZE) as $idBatch) {
                $records = $reportService->getReportQueryByIds($idBatch, $params)->get()->all();
                $processedRecords = $reportService->processConsolidatedChunk($records, $params);

                foreach ($processedRecords as $record) {
                    yield $this->mapConsolidatedReportRow($record);
                }
            }
        } else {
            $query = $reportService->getReportQuery($params);
            $sqlRecordsBatch = [];

            foreach ($query->lazyById(self::BATCH_SIZE, 'pqr.id', 'id') as $sqlRecord) {
                $sqlRecordsBatch[] = $sqlRecord;

                if (count($sqlRecordsBatch) >= self::UUID_BATCH_SIZE) {
                    $processedRecords = $reportService->processConsolidatedChunk($sqlRecordsBatch, $params);

                    foreach ($processedRecords as $record) {
                        yield $this->mapConsolidatedReportRow($record);
                    }

                    $sqlRecordsBatch = [];
                }
            }

            if (! empty($sqlRecordsBatch)) {
                $processedRecords = $reportService->processConsolidatedChunk($sqlRecordsBatch, $params);

                foreach ($processedRecords as $record) {
                    yield $this->mapConsolidatedReportRow($record);
                }
            }
        }
    }

    private function getConsolidatedReportHeaders(): array
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
        ];
    }

    private function mapConsolidatedReportRow($record): array
    {
        $quoteType = $record->quote_type ?? (isset($record->code) ? explode('-', $record->code)[0] : 'N/A');
        $fallbacks = isset($record->fallbacks) && $record->fallbacks === 0 ? 'N/A' : ($record->fallbacks ?? 'N/A');
        $saleLeads = in_array(
            $record->quote_status_id ?? null,
            [QuoteStatusEnum::TransactionApproved, QuoteStatusEnum::PolicyIssued,
                QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked]
        ) ? 'Yes' : 'No';

        return [
            $quoteType,
            $record->code ?? 'N/A',
            $record->lead_created_at ?? 'N/A',
            $record->chat_initiated_at ?? ($record->date_of_first_interaction ?? 'N/A'),
            $this->formatCommunicationChannel($record->communication_channels ?? []),
            $record->quote_batch_id_text ?? 'N/A',
            $record->renewal_batch_text ?? 'N/A',
            $record->transaction_type_text ?? 'N/A',
            $record->segment ?? 'N/A',
            $record->customer_interactions ?? 0,
            $record->ai_interactions ?? 0,
            $record->total_ai_interactions ?? 0,
            $fallbacks,
            $record->payment_status ?? 'N/A',
            $saleLeads,
            $record->provider_name ?? 'N/A',
            $record->plan_type ?? 'N/A',
            $record->plan_name ?? 'N/A',
            $record->total_price ?? 'N/A',
            $record->payment_paid_at ?? 'N/A',
            $record->paid_at ?? 'N/A',
            $record->advisor_assigned_date ?? 'N/A',
            $record->lead_assignment_trigger_text ?? 'N/A',
        ];
    }

    private function uploadCsvToAzure($stream, string $azurePath): array
    {
        rewind($stream);
        $csvContent = stream_get_contents($stream);
        fclose($stream);
        Storage::disk(self::AZURE_DISK)->put($azurePath, $csvContent);

        // @phpstan-ignore-next-line
        $url = Storage::disk(self::AZURE_DISK)->temporaryUrl(
            $azurePath,
            now()->addHours(self::URL_EXPIRY_HOURS)
        );

        DeleteTempOCBPDFFileJob::dispatch($azurePath)
            ->delay(now()->addHours(self::URL_EXPIRY_HOURS));

        return [
            'path' => $azurePath,
            'url' => $url,
        ];
    }

    private function formatCommunicationChannel($channel): string
    {
        if ($channel instanceof BSONDocument || $channel instanceof BSONArray) {
            $channel = $channel->getArrayCopy();
        }

        if (is_string($channel)) {
            return $channel === '' ? 'N/A' : $channel;
        }

        if (! is_array($channel)) {
            return 'N/A';
        }

        $channels = array_filter($channel, function ($item) {
            return is_string($item) && ! empty($item);
        });

        sort($channels);

        return empty($channels) ? 'N/A' : implode(', ', $channels);
    }

    private function formatTimePeriod(array $params): string
    {
        if (! empty($params['chat_initiated_at']) && is_array($params['chat_initiated_at']) && count($params['chat_initiated_at']) === 2) {
            $startDate = Carbon::parse($params['chat_initiated_at'][0])->format('Y-m-d');
            $endDate = Carbon::parse($params['chat_initiated_at'][1])->format('Y-m-d');

            return $startDate.' to '.$endDate;
        }

        return 'All Time';
    }
}
