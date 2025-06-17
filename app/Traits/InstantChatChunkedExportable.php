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
        $flushInterval = 2500;

        DB::setDefaultConnection('mysql_read');

        LoggerService::info('Starting chunked instant chat export processing');

        try {
            $instantAlfredService = app(InstantAlfredService::class);

            $query->chunk($chunkSize, function ($sqlRecords) use (
                $instantAlfredService,
                $requestParams,
                $stream,
                &$totalRecords,
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

                // Flush to disk and manage memory periodically
                if ($totalRecords % $flushInterval === 0) {
                    fflush($stream);
                    gc_collect_cycles();
                    LoggerService::info("Instant chat export progress: {$totalRecords} records processed");
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

        // Final flush
        fflush($stream);
        LoggerService::info("Chunked instant chat export completed: {$totalRecords} total records");

        return $totalRecords;
    }
}
