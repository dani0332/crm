<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CsvExportableInterface;
use App\Services\Logger\LoggerService;

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

        $stream = fopen($csvFilePath, 'w');
        if (! $stream) {
            throw new \RuntimeException("Cannot create CSV file at: {$csvFilePath}");
        }

        try {
            // Write CSV headers
            fputcsv($stream, $exporter->headings());

            $totalRecords = $this->writeDataToStream($stream, $exporter, $requestParams);
            if ($totalRecords > 0) {
                $totalRecords = $totalRecords - 1;
            }
            fclose($stream);

            LoggerService::info("CSV export completed. Records: {$totalRecords}, File: {$fileName}.csv");

            return [
                'filePath' => $csvFilePath,
                'recordCount' => $totalRecords,
            ];

        } catch (\Throwable $e) {
            fclose($stream);
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

        // Try to use query builder for chunked processing
        $query = $exporter->getQuery($requestParams);

        if ($query) {
            LoggerService::info('Using chunked query processing for CSV export');

            $query->chunk($chunkSize, function ($records) use ($stream, $exporter, &$totalRecords, $chunkSize) {
                foreach ($records as $record) {
                    fputcsv($stream, $exporter->map($record));
                    $totalRecords++;
                }

                // Force garbage collection to manage memory
                if ($totalRecords % ($chunkSize * 5) === 0) {
                    gc_collect_cycles();
                }
            });
        } else {
            // Fallback to collection method
            LoggerService::info('Using collection method for CSV export');

            $data = $exporter->collection($requestParams);
            foreach ($data as $record) {
                fputcsv($stream, $exporter->map($record));
                $totalRecords++;

                // Manage memory for large datasets
                if ($totalRecords % 100 === 0) {
                    gc_collect_cycles();
                }
            }
        }

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
