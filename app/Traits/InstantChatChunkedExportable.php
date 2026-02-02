<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\InstantAlfredService;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Facades\DB;

trait InstantChatChunkedExportable
{
    /**
     * Custom chunked query processing for instant chat exports
     * This handles the complex SQL + MongoDB integration
     */
    public function processChunkedQuery($query, array $requestParams, $stream): int
    {
        $totalRecords = 0;
        $chunkSize = 500; // Smaller chunks due to MongoDB processing overhead
        $flushInterval = 500; // Reduced from 2500 for better responsiveness

        DB::setDefaultConnection('mysql_read');

        LoggerService::info('Starting chunked instant chat export processing');

        try {
            $instantAlfredService = app(InstantAlfredService::class);
            $isFirstChunk = true;

            $query->chunk($chunkSize, function ($sqlRecords) use (
                $instantAlfredService,
                $requestParams,
                $stream,
                &$totalRecords,
                &$isFirstChunk,
                $flushInterval
            ) {
                // Process this chunk through InstantAlfredService for MongoDB integration
                $processedRecords = $instantAlfredService->processConsolidatedChunk(
                    $sqlRecords,
                    $requestParams
                );

                // Write processed records to CSV
                foreach ($processedRecords as $record) {
                    $mappedData = $this->map($record);
                    fputcsv($stream, $mappedData);
                    $totalRecords++;
                }

                // Flush after every chunk to ensure continuous output and prevent timeouts
                fflush($stream);
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                // Additional flush and memory management at intervals
                if ($totalRecords % $flushInterval === 0) {
                    gc_collect_cycles();
                    LoggerService::info("Instant chat export progress: {$totalRecords} records processed");
                }

                // Immediate flush after first chunk to send data to browser quickly
                if ($isFirstChunk) {
                    $isFirstChunk = false;
                    LoggerService::info("First chunk processed: {$totalRecords} records sent to browser");
                }
            });

        } catch (\Throwable $e) {
            LoggerService::error('Error in chunked instant chat export', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            throw $e;
        } finally {
            DB::setDefaultConnection('mysql');
        }

        // Final flush - ensure all data is sent to browser
        fflush($stream);
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
        LoggerService::info("Chunked instant chat export completed: {$totalRecords} total records");

        return $totalRecords;
    }
}
