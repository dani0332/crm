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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use MongoDB\BSON\UTCDateTime;

class InstantAlfredExportService
{
    private const AZURE_DISK = 'azureIMPrivate';
    private const URL_EXPIRY_HOURS = 1;
    private const BATCH_SIZE = 500;
    private const FLUSH_INTERVAL = 1000;

    public function exportAndEmail(array $params): array
    {
        $startTime = microtime(true);

        $sqlData = $this->fetchSqlData($params);
        $uuids = array_keys($sqlData);

        if (empty($uuids)) {
            Log::info('Starting Instant Alfred export - no data found', ['quotes' => 0]);
        } else {
            Log::info('Starting Instant Alfred export', ['quotes' => count($uuids)]);
        }

        $result = $this->generateAndUploadCsv($uuids, $sqlData, $params);

        $emailSent = false;
        try {
            $this->sendDownloadEmail($result['url'], $params, $result['records'], InstantChatReportsEnum::DETAILED_REPORT);
            $emailSent = true;
            Log::info('Export email sent successfully', [
                'recipient' => $params['recipientEmail'],
                'subject' => $params['subject'] ?? 'Your Export is Ready',
                'records' => $result['records'],
                'download_url' => $result['url'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send export email', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'recipient' => $params['recipientEmail'],
                'subject' => $params['subject'] ?? 'Your Export is Ready',
                'download_url' => $result['url'],
            ]);
        }

        DeleteTempOCBPDFFileJob::dispatch($result['path'])
            ->delay(now()->addMinutes(60));

        $totalDuration = round(microtime(true) - $startTime, 2);
        Log::info('Detailed export completed', [
            'total_duration' => $totalDuration,
            'records' => $result['records'],
            'email_sent' => $emailSent,
            'download_url' => $result['url'],
        ]);

        return [
            'success' => true,
            'records' => $result['records'],
            'duration' => $totalDuration,
            'email_sent' => $emailSent,
            'download_url' => $result['url'],
        ];
    }

    private function fetchSqlData(array $params): array
    {
        DB::setDefaultConnection('mysql_read');

        try {
            $startTime = microtime(true);
            $query = app(InstantAlfredService::class)
                ->getChatDetailedReportQuery($params);

            $sqlData = $query->get()
                ->keyBy('uuid')
                ->map(fn ($item) => (array) $item)
                ->toArray();

            $sqlTime = round(microtime(true) - $startTime, 3);
            Log::info('SQL query executed', [
                'time' => $sqlTime,
                'records' => count($sqlData),
            ]);

            return $sqlData;
        } finally {
            DB::setDefaultConnection('mysql');
        }
    }

    private function generateAndUploadCsv(array $uuids, array $sqlData, array $params): array
    {
        $csvStartTime = microtime(true);
        $fileName = 'detailed_report_'.now()->format('Y-m-d_His').'_'.uniqid().'.csv';
        $azurePath = "temp/exports/{$fileName}";

        $stream = fopen('php://temp/maxmemory:10485760', 'r+');
        $records = 0;

        try {
            $writeStartTime = microtime(true);
            fputcsv($stream, $this->getHeaders());

            if (! empty($uuids)) {
                foreach ($this->streamMongoData($uuids, $sqlData, $params) as $row) {
                    fputcsv($stream, $row);
                    $records++;

                    if ($records % self::FLUSH_INTERVAL === 0) {
                        gc_collect_cycles();
                        Log::info("Export progress: {$records} records");
                    }
                }
            }
            $writeTime = round(microtime(true) - $writeStartTime, 3);

            rewind($stream);
            $uploadStartTime = microtime(true);
            $csvContent = stream_get_contents($stream);
            fclose($stream);
            Storage::disk(self::AZURE_DISK)->put($azurePath, $csvContent);
            $uploadTime = round(microtime(true) - $uploadStartTime, 3);

            $urlStartTime = microtime(true);
            // @phpstan-ignore-next-line
            $url = Storage::disk(self::AZURE_DISK)->temporaryUrl(
                $azurePath,
                now()->addHours(self::URL_EXPIRY_HOURS)
            );
            $urlTime = round(microtime(true) - $urlStartTime, 3);

            $totalCsvTime = round(microtime(true) - $csvStartTime, 3);
            Log::info('CSV generation and upload complete', [
                'records' => $records,
                'path' => $azurePath,
                'csv_write_time' => $writeTime,
                'azure_upload_time' => $uploadTime,
                'url_generation_time' => $urlTime,
                'total_csv_time' => $totalCsvTime,
                'time_per_record' => $records > 0 ? round($totalCsvTime / $records * 1000, 3) : 0,
            ]);

            return ['path' => $azurePath, 'url' => $url, 'records' => $records];

        } catch (\Throwable $e) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            Log::error('CSV generation failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    private function streamMongoData(array $uuids, array $sqlData, array $params): \Generator
    {
        $pipeline = $this->buildPipeline($uuids, $params);

        $mongoStartTime = microtime(true);
        $cursor = AlfredChat::raw(fn ($collection) => $collection->aggregate($pipeline, [
            'allowDiskUse' => true,
            'cursor' => ['batchSize' => self::BATCH_SIZE],
        ]));

        $recordCount = 0;
        foreach ($cursor as $doc) {
            $recordCount++;
            yield $this->mapToRow($doc, $sqlData);
        }

        $mongoTime = round(microtime(true) - $mongoStartTime, 3);
        Log::info('MongoDB aggregation executed', [
            'time' => $mongoTime,
            'records' => $recordCount,
            'time_per_record' => $recordCount > 0 ? round($mongoTime / $recordCount * 1000, 3) : 0,
        ]);
    }

    private function buildPipeline(array $uuids, array $params): array
    {
        $sortDir = isset($params['sortType']) && $params['sortType'] === 'desc' ? -1 : 1;

        $matchConditions = ['quote_id' => ['$in' => $uuids]];

        if (! empty($params['chat_initiated_at']) && is_array($params['chat_initiated_at'])) {
            // Parse dates as UTC dates (not local timezone) to match MongoDB's UTC storage
            // This ensures "2025-11-30" is treated as "2025-11-30 UTC", not "2025-11-30 Asia/Dubai"
            // @phpstan-ignore-next-line
            $dateFrom = new UTCDateTime(Carbon::parse($params['chat_initiated_at'][0], 'UTC')->startOfDay()->timestamp * 1000);
            // @phpstan-ignore-next-line
            $dateTo = new UTCDateTime(Carbon::parse($params['chat_initiated_at'][1], 'UTC')->endOfDay()->timestamp * 1000);
            $matchConditions['created_at'] = ['$gte' => $dateFrom, '$lte' => $dateTo];
        }

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

    private function getHeaders(): array
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
            'Input Tokens',
            'Completion Tokens',
            'Total Tokens',
        ];
    }

    private function mapToRow($doc, array $sqlData): array
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
            $doc['communication_channel'] ?? 'N/A',
            $doc['employee_flag'] ?? 'N/A',
            $doc['email'] ?? 'N/A',
            $doc['input_tokens_usage'] ?? 0,
            $doc['completion_tokens'] ?? 0,
            $doc['total_tokens'] ?? 0,
        ];
    }

    public function generateCsvAndGetUrl(array $params): array
    {
        $apiStartTime = microtime(true);
        $reportType = $params['report'] ?? InstantChatReportsEnum::DETAILED_REPORT;

        Log::info('API: Starting CSV generation for URL', [
            'report' => $reportType,
            'recipient' => $params['recipientEmail'] ?? 'N/A',
        ]);

        if ($reportType === InstantChatReportsEnum::CONSOLIDATED_REPORT) {
            $sqlStartTime = microtime(true);
            DB::setDefaultConnection('mysql_read');
            try {
                $instantAlfredService = app(InstantAlfredService::class);
                $query = $instantAlfredService->getChatConsolidateReportQuery($params);
                $sqlData = $query->get();
                $sqlTime = round(microtime(true) - $sqlStartTime, 3);
                Log::info('API: SQL query completed', ['time' => $sqlTime, 'records' => $sqlData->count()]);
            } finally {
                DB::setDefaultConnection('mysql');
            }

            $result = $this->generateConsolidatedCsv($params);
        } else {
            $sqlData = $this->fetchSqlData($params);
            $uuids = array_keys($sqlData);
            $result = $this->generateAndUploadCsv($uuids, $sqlData, $params);
        }

        $totalApiTime = round(microtime(true) - $apiStartTime, 3);
        Log::info('API: CSV generation completed - URL ready', [
            'total_api_time' => $totalApiTime,
            'records' => $result['records'],
            'download_url' => $result['url'],
            'report' => $reportType,
        ]);

        return [
            'success' => true,
            'download_url' => $result['url'],
            'records' => $result['records'],
            'total_time_seconds' => $totalApiTime,
            'report_type' => $reportType,
        ];
    }

    public function exportConsolidatedAndEmail(array $params): array
    {
        $startTime = microtime(true);

        if (empty($params['report'])) {
            $params['report'] = InstantChatReportsEnum::CONSOLIDATED_REPORT;
        }

        Log::info('Starting Instant Alfred consolidated export', ['params' => array_keys($params)]);

        $result = $this->generateConsolidatedCsv($params);

        $emailSent = false;
        try {
            $this->sendDownloadEmail($result['url'], $params, $result['records'], InstantChatReportsEnum::CONSOLIDATED_REPORT);
            $emailSent = true;
            Log::info('Consolidated export email sent successfully', [
                'recipient' => $params['recipientEmail'],
                'subject' => $params['subject'] ?? 'Your Export is Ready',
                'records' => $result['records'],
                'download_url' => $result['url'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send consolidated export email', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'recipient' => $params['recipientEmail'],
                'subject' => $params['subject'] ?? 'Your Export is Ready',
                'download_url' => $result['url'],
            ]);
        }

        DeleteTempOCBPDFFileJob::dispatch($result['path'])
            ->delay(now()->addMinutes(60));

        $totalDuration = round(microtime(true) - $startTime, 2);
        Log::info('Consolidated export completed', [
            'total_duration' => $totalDuration,
            'records' => $result['records'],
            'email_sent' => $emailSent,
            'download_url' => $result['url'],
        ]);

        return [
            'success' => true,
            'records' => $result['records'],
            'duration' => $totalDuration,
            'email_sent' => $emailSent,
            'download_url' => $result['url'],
        ];
    }

    private function generateConsolidatedCsv(array $params): array
    {
        $csvStartTime = microtime(true);
        $fileName = 'consolidated_report_'.now()->format('Y-m-d_His').'_'.uniqid().'.csv';
        $azurePath = "temp/exports/{$fileName}";

        $stream = fopen('php://temp/maxmemory:10485760', 'r+');
        $records = 0;

        try {
            $writeStartTime = microtime(true);
            fputcsv($stream, $this->getConsolidatedHeaders());

            foreach ($this->streamConsolidatedData($params) as $row) {
                fputcsv($stream, $row);
                $records++;

                if ($records % self::FLUSH_INTERVAL === 0) {
                    gc_collect_cycles();
                    Log::info("Consolidated export progress: {$records} records");
                }
            }
            $writeTime = round(microtime(true) - $writeStartTime, 3);

            rewind($stream);
            $uploadStartTime = microtime(true);
            $csvContent = stream_get_contents($stream);
            fclose($stream);
            Storage::disk(self::AZURE_DISK)->put($azurePath, $csvContent);
            $uploadTime = round(microtime(true) - $uploadStartTime, 3);

            $urlStartTime = microtime(true);
            // @phpstan-ignore-next-line
            $url = Storage::disk(self::AZURE_DISK)->temporaryUrl(
                $azurePath,
                now()->addHours(self::URL_EXPIRY_HOURS)
            );
            $urlTime = round(microtime(true) - $urlStartTime, 3);

            $totalCsvTime = round(microtime(true) - $csvStartTime, 3);
            Log::info('Consolidated CSV generation and upload complete', [
                'records' => $records,
                'path' => $azurePath,
                'csv_write_time' => $writeTime,
                'azure_upload_time' => $uploadTime,
                'url_generation_time' => $urlTime,
                'total_csv_time' => $totalCsvTime,
                'time_per_record' => $records > 0 ? round($totalCsvTime / $records * 1000, 3) : 0,
            ]);

            return ['path' => $azurePath, 'url' => $url, 'records' => $records];

        } catch (\Throwable $e) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            Log::error('Consolidated CSV generation failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    private function streamConsolidatedData(array $params): \Generator
    {
        DB::setDefaultConnection('mysql_read');

        try {
            $sqlStartTime = microtime(true);
            $instantAlfredService = app(InstantAlfredService::class);
            $query = $instantAlfredService->getChatConsolidateReportQuery($params);
            $chunkSize = 500;

            $totalRecords = 0;
            $mongoTotalTime = 0;
            $chunkCount = 0;

            $sqlRecordsChunk = [];
            foreach ($query->lazyById($chunkSize, 'pqr.id') as $sqlRecord) {
                $sqlRecordsChunk[] = $sqlRecord;

                if (count($sqlRecordsChunk) >= $chunkSize) {
                    $chunkCount++;
                    $mongoStartTime = microtime(true);
                    $processedRecords = $instantAlfredService->processConsolidatedChunk($sqlRecordsChunk, $params);
                    $mongoTotalTime += microtime(true) - $mongoStartTime;

                    foreach ($processedRecords as $record) {
                        $totalRecords++;
                        yield $this->mapConsolidatedRow($record);
                    }

                    $sqlRecordsChunk = [];
                }
            }

            if (! empty($sqlRecordsChunk)) {
                $chunkCount++;
                $mongoStartTime = microtime(true);
                $processedRecords = $instantAlfredService->processConsolidatedChunk($sqlRecordsChunk, $params);
                $mongoTotalTime += microtime(true) - $mongoStartTime;

                foreach ($processedRecords as $record) {
                    $totalRecords++;
                    yield $this->mapConsolidatedRow($record);
                }
            }

            $sqlTime = round(microtime(true) - $sqlStartTime, 3);
            Log::info('Consolidated export queries executed', [
                'sql_time' => $sqlTime,
                'mongodb_time' => round($mongoTotalTime, 3),
                'total_records' => $totalRecords,
                'chunks' => $chunkCount,
            ]);
        } finally {
            DB::setDefaultConnection('mysql');
        }
    }

    private function getConsolidatedHeaders(): array
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

    private function mapConsolidatedRow($record): array
    {
        $quoteType = $record->quote_type ?? (isset($record->code) ? explode('-', $record->code)[0] : 'N/A');
        $code = $record->code ?? 'N/A';
        $leadCreatedAt = $record->lead_created_at ?? 'N/A';
        $dateOfFirstInteraction = $record->chat_initiated_at ?? ($record->date_of_first_interaction ?? 'N/A');
        $communicationChannels = $this->formatCommunicationChannel($record->communication_channels ?? []);
        $quoteBatchIdText = $record->quote_batch_id_text ?? 'N/A';
        $renewalBatchIdText = $record->renewal_batch_id_text ?? 'N/A';
        $transactionTypeText = $record->transaction_type_text ?? 'N/A';
        $segment = $record->segment ?? 'N/A';
        $customerInteractions = $record->customer_interactions ?? 0;
        $aiInteractions = $record->ai_interactions ?? 0;
        $totalAiInteractions = $record->total_ai_interactions ?? 0;
        $fallbacks = isset($record->fallbacks) && $record->fallbacks === 0 ? 'N/A' : ($record->fallbacks ?? 'N/A');
        $paymentStatus = $record->payment_status ?? 'N/A';
        $saleLeads = in_array(
            $record->quote_status_id ?? null,
            [QuoteStatusEnum::TransactionApproved, QuoteStatusEnum::PolicyIssued,
                QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked]
        ) ? 'Yes' : 'No';
        $providerName = $record->provider_name ?? 'N/A';
        $planType = $record->plan_type ?? 'N/A';
        $planName = $record->plan_name ?? 'N/A';
        $totalPrice = $record->total_price ?? 'N/A';
        $paymentPaidAt = $record->payment_paid_at ?? 'N/A';
        $paidAt = $record->paid_at ?? 'N/A';
        $advisorAssignedDate = $record->advisor_assigned_date ?? 'N/A';
        $leadAssignmentTriggerText = $record->lead_assignment_trigger_text ?? 'N/A';

        return [
            $quoteType,
            $code,
            $leadCreatedAt,
            $dateOfFirstInteraction,
            $communicationChannels,
            $quoteBatchIdText,
            $renewalBatchIdText,
            $transactionTypeText,
            $segment,
            $customerInteractions,
            $aiInteractions,
            $totalAiInteractions,
            $fallbacks,
            $paymentStatus,
            $saleLeads,
            $providerName,
            $planType,
            $planName,
            $totalPrice,
            $paymentPaidAt,
            $paidAt,
            $advisorAssignedDate,
            $leadAssignmentTriggerText,
        ];
    }

    private function formatCommunicationChannel($channel): string
    {
        if ($channel instanceof \MongoDB\Model\BSONDocument || $channel instanceof \MongoDB\Model\BSONArray) {
            $channel = $channel->getArrayCopy();
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

    private function sendDownloadEmail(string $url, array $params, int $records, string $reportType = InstantChatReportsEnum::DETAILED_REPORT): void
    {
        $exportTitle = $reportType === InstantChatReportsEnum::CONSOLIDATED_REPORT
            ? 'Instant Alfred Consolidated Report'
            : 'Instant Alfred Detailed Report';

        Mail::send('ExportDownloadMail', [
            'recipientName' => $params['recipientName'] ?? 'User',
            'exportTitle' => $exportTitle,
            'downloadUrl' => $url,
            'recordCount' => $records,
            'expiryHours' => self::URL_EXPIRY_HOURS,
            'currentDate' => now()->format('d-m-Y'),
        ], function ($message) use ($params) {
            $message->to($params['recipientEmail']);
            $message->subject($params['subject'] ?? 'Your Export is Ready');
            $message->from(
                config('constants.MAIL_FROM_ADDRESS'),
                config('constants.MAIL_FROM_NAME')
            );
        });
    }
}
