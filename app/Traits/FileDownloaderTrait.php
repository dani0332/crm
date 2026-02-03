<?php

namespace App\Traits;

use GuzzleHttp\Client;
use Maatwebsite\Excel\Facades\Excel;

trait FileDownloaderTrait
{
    public function fetchFileFromUrl($url, $renewalsUploadLead, $import)
    {
        $client = new Client;
        $response = $client->get($url);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to download file from URL');
        }

        // Create temp directory if it doesn't exist
        $tempDir = storage_path('temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Create temporary file with .xlsx extension
        $tempFileName = 'excel_import_'.uniqid().'.xlsx';
        $filePath = $tempDir.'/'.$tempFileName;

        // Write the downloaded content to the temporary file
        file_put_contents($filePath, $response->getBody()->getContents());

        try {
            Excel::import($import, $filePath);

            $validRows = $import->getValidCount();
            $failedRows = $import->getFailedCount();

            $renewalsUploadLead->update([
                'cannot_upload' => $failedRows,
                'good' => 0,
                'total_records' => ($validRows + $failedRows),
            ]);
        } finally {
            // Clean up the temporary file
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
}
