<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CsvExportableInterface;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Facades\DB;

class CsvExportService
{
    /**
     * Generate CSV file and return the file path
     */
    public function generateCsvFile(CsvExportableInterface $exporter, array $requestParams = []): string
    {
        $result = $this->generateCsvFileWithCount($exporter, $requestParams);

        return $result['filePath'];
    }

    /**
     * Generate CSV file and return file path with record count
     */
    public function generateCsvFileWithCount(CsvExportableInterface $exporter, array $requestParams = []): array
    {
        $fileName = $this->generateFileName($requestParams);
        $csvFilePath = storage_path("temp/{$fileName}.csv");

        // Ensure temp directory exists
        $tempDir = dirname($csvFilePath);
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Start with write mode to create/truncate file for headers
        $stream = fopen($csvFilePath, 'w');
        if (! $stream) {
            throw new \RuntimeException("Cannot create CSV file at: {$csvFilePath}");
        }

        try {
            DB::setDefaultConnection('mysql_read');
            // Write CSV headers first
            fputcsv($stream, $exporter->headings());
            fclose($stream);

            // Reopen in append mode for chunked data writing
            $stream = fopen($csvFilePath, 'a');
            if (! $stream) {
                throw new \RuntimeException("Cannot reopen CSV file in append mode: {$csvFilePath}");
            }

            $totalRecords = $this->writeDataToStream($stream, $exporter, $requestParams);

            fclose($stream);

            LoggerService::info("CSV export completed. Records: {$totalRecords}, File: {$fileName}.csv");
            DB::setDefaultConnection('mysql');

            return [
                'filePath' => $csvFilePath,
                'recordCount' => $totalRecords,
            ];

        } catch (\Throwable $e) {
            fclose($stream);
            DB::setDefaultConnection('mysql');
            $this->cleanupFile($csvFilePath);
            throw $e;
        }
    }

    /**
     * Write data to CSV stream using memory-efficient chunking
     */
    private function writeDataToStream($stream, CsvExportableInterface $exporter, array $requestParams): int
    {
        $totalRecords = 0;
        $chunkSize = 1000;
        $flushInterval = 5000; // Flush to disk every 5000 records

        // Try to use query builder for chunked processing
        $query = $exporter->getQuery($requestParams);

        if ($query) {
            LoggerService::sql('Export chunked', $query);
            LoggerService::info('Using chunked query processing for CSV export');

            // Check if exporter has custom chunked processing
            if (method_exists($exporter, 'processChunkedQuery')) {
                LoggerService::info('Using custom chunked processing for export');
                $totalRecords = $exporter->processChunkedQuery($query, $requestParams, $stream);
            } else {
                LoggerService::info('Using default chunked processing for export');
                // Default chunked processing
                $query->chunk($chunkSize, function ($records) use ($stream, $exporter, &$totalRecords, $flushInterval) {
                    $chunkBuffer = [];

                    foreach ($records as $record) {
                        $chunkBuffer[] = $exporter->map($record);
                        $totalRecords++;
                    }

                    // Write chunk buffer to file
                    foreach ($chunkBuffer as $row) {
                        fputcsv($stream, $row);
                    }

                    // Flush to disk and manage memory periodically
                    if ($totalRecords % $flushInterval === 0) {
                        fflush($stream); // Force write to disk
                        gc_collect_cycles(); // Garbage collection
                        // LoggerService::info("CSV export progress: {$totalRecords} records written");
                    }

                    // Clear chunk buffer to free memory
                    unset($chunkBuffer);
                });
            }
        } else {
            // Fallback to collection method with chunked writing
            LoggerService::info('Using collection method for CSV export');

            $data = $exporter->collection($requestParams);
            $rowBuffer = [];
            $bufferSize = 100;

            foreach ($data as $record) {
                $rowBuffer[] = $exporter->map($record);
                $totalRecords++;

                // Write buffer when it reaches buffer size
                if (count($rowBuffer) >= $bufferSize) {
                    foreach ($rowBuffer as $row) {
                        fputcsv($stream, $row);
                    }
                    $rowBuffer = []; // Clear buffer

                    // Flush and manage memory periodically
                    if ($totalRecords % $flushInterval === 0) {
                        fflush($stream);
                        gc_collect_cycles();
                        // LoggerService::info("CSV export progress: {$totalRecords} records written");
                    }
                }
            }

            // Write remaining buffer
            foreach ($rowBuffer as $row) {
                fputcsv($stream, $row);
            }
        }

        // Final flush to ensure all data is written
        fflush($stream);
        LoggerService::info("CSV export completed: {$totalRecords} total records written");

        return $totalRecords;
    }

    /**
     * Generate unique filename with timestamp
     */
    private function generateFileName(array $requestParams): string
    {
        $fileName = $requestParams['fileName'] ?? 'export';

        return $fileName.'-'.now()->format('Y-m-d-H-i-s');
    }

    /**
     * Clean up temporary file
     */
    public function cleanupFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Get file size in KB
     */
    public function getFileSizeInKb(string $filePath): float
    {
        return file_exists($filePath) ? round(filesize($filePath) / 1024, 2) : 0;
    }
}
