<?php

namespace App\Traits;

use App\Enums\EnvEnum;
use App\Jobs\ExportCsvAndSendEmailJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait ExcelExportable
{
    abstract public function collection($requestParams);
    abstract public function headings();
    abstract public function map($quote);

    /**
     * Download CSV file directly
     */
    public function download($fileName)
    {
        $fileName = $fileName.'-'.Carbon::now()->format('Y-m-d');

        return new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $this->headings());
            $data = $this->collection([]);
            foreach ($data as $quote) {
                fputcsv($handle, $this->map($quote));
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'.csv"',
        ]);
    }

    /**
     * Queue a job to send the CSV as an email attachment
     */
    public function emailCSV($fileName, $requestParams = [])
    {
        $fileName = $fileName.'-'.Carbon::now()->format('Y-m-d');

        if (empty($requestParams['recipientEmail'])) {
            // To will be currentUserID
            if (auth()->check()) {
                $currentUser = User::where('id', '=', auth()->user()->id);
                $requestParams['recipientEmail'] = $currentUser->value('email');
                $requestParams['recipientName'] = $currentUser->value('name');
            } else {
                return response()->json([
                    'error' => 'User not authenticated',
                    'message' => 'Cannot send email as the user is not authenticated.',
                ], 401);
            }
        }

        $currentDate = Carbon::now()->format('d-m-Y');
        $requestParams['fileName'] = $fileName;

        if (! empty($requestParams['exportTitle'])) {
            $requestParams['exportTitle'] = ucfirst($requestParams['exportTitle']);
        } else {
            $requestParams['exportTitle'] = ucfirst($requestParams['quoteType'] ?? 'Data');
        }

        if (empty($requestParams['subject'])) {
            $requestParams['subject'] = "{$requestParams['exportTitle']} Export - {$currentDate}";
        }

        // Dispatch job to process CSV generation and email sending
        ExportCsvAndSendEmailJob::dispatch(
            get_class($this),
            $requestParams['recipientEmail'],
            $requestParams
        );
        info('Dispatched ExportCsvAndSendEmailJob');

        // Return response to the user that export is being processed
        return response()->json([
            'message' => 'Your export is being processed. You will receive an email with the CSV file shortly.',
        ]);
    }

    /**
     * Send email with CSV data as attachment using memory-efficient chunking
     *
     * @param  string|array  $recipientEmail  Recipient email(s)
     * @param  string  $emailSubject  Email subject
     * @param  array  $requestParams  Parameters for the email template
     * @param  array  $ccRecipients  CC recipients
     * @param  string  $fileName  Filename for the CSV attachment (without extension)
     * @return void
     */
    public function sendEmailWithCSVAttachment($recipientEmail, $emailSubject, $requestParams, $ccRecipients = [], $fileName = 'export')
    {
        // Track memory usage during export
        $initialMemory = memory_get_usage(true) / 1024 / 1024;
        logger()->info("CSV export started. Initial memory: {$initialMemory}MB");

        if (! isset($this->quoteType)) {
            $this->quoteType = $requestParams['quoteType'] ?? null;
        }

        // Get environment variables for email configuration
        $emailL_sys = config('constants.APP_ENV');

        // Set email from details based on environment
        if ($emailL_sys == EnvEnum::PRODUCTION) {
            $fromEmail = config('constants.MAIL_FROM_ADDRESS_AML', config('constants.MAIL_FROM_ADDRESS'));
            $fromName = config('constants.MAIL_FROM_NAME_AML', config('constants.MAIL_FROM_NAME'));
        } else {
            $fromEmail = config('constants.MAIL_FROM_ADDRESS');
            $fromName = config('constants.MAIL_FROM_NAME');
        }

        // Generate CSV content in memory using chunking
        $csvFileName = $fileName.'.csv';
        $stream = fopen('php://temp', 'r+');

        $requestParams['user'] = User::where(['email' => $requestParams['recipientEmail']])->first();

        // Write CSV headers
        fputcsv($stream, $this->headings());

        $startDate = isset($requestParams['created_at_start']) ? Carbon::parse($requestParams['created_at_start'])->format('d M Y') : '';
        $endDate = isset($requestParams['created_at_end']) ? Carbon::parse($requestParams['created_at_end'])->format('d M Y') : '';

        if ($startDate && $endDate) {
            $diff = abs(Carbon::parse($requestParams['created_at_start'])->diffInDays(Carbon::parse($requestParams['created_at_end']))) + 1;
            logger()->debug("Processing export between: {$startDate} - {$endDate} ({$diff} days)");
        }

        // Process data in memory-efficient chunks
        logger()->info("Starting CSV data export with chunking");

        $totalRecords = 0;
        $chunkSize = 500; // Adjust based on your data complexity
        $exportName = class_basename($this);
        $chunkCount = 0;
        $totalChunkTime = 0;

        try {
            // Use the query builder version of collection if available
            if (method_exists($this, 'getQuery')) {
                $query = $this->getQuery($requestParams);

                // Use database chunking for efficient memory usage
                $query->chunk($chunkSize, function ($records) use ($stream, &$totalRecords, &$chunkCount, &$totalChunkTime, $exportName) {
                    $chunkStartTime = microtime(true);
                    $chunkCount++;
                    $recordCount = count($records);

                    logger()->debug("Processing chunk #{$chunkCount} with {$recordCount} records");

                    // Track memory before mapping records
                    $memoryBeforeMapping = round(memory_get_usage(true) / 1024 / 1024, 2);

                    $i = 0;
                    foreach ($records as $record) {
                        // Debug for the first record
                        if ($chunkCount === 1 && $i === 0) {
                            logger()->debug("{$exportName}: First record attributes", [
                                'record_keys' => array_keys((array)$record->getAttributes()),
                                'relation_keys' => array_keys((array)$record->getRelations())
                            ]);
                        }

//                        $startMapTime = microtime(true);
                        $mappedRow = $this->map($record);
//                        $mapTime = round((microtime(true) - $startMapTime) * 1000, 2); // in milliseconds
                        $i++;

                        fputcsv($stream, $mappedRow);
                        $totalRecords++;
                    }

                    $currentMemory = round(memory_get_usage(true) / 1024 / 1024, 2);
                    $memoryDiff = $currentMemory - $memoryBeforeMapping;
                    $chunkTime = round((microtime(true) - $chunkStartTime) * 1000, 2); // in milliseconds
                    $totalChunkTime += $chunkTime;

                    logger()->debug("{$exportName}: Chunk #{$chunkCount} processed. Records: {$recordCount}, Memory: {$currentMemory}MB, Memory diff: {$memoryDiff}MB, Chunk Time: {$chunkTime}ms, Total time: {$totalChunkTime}ms");

                    // Force garbage collection to free memory
                    gc_collect_cycles();
                });

                // Log summary statistics when complete
                if ($exportName === 'HealthQuotesExport') {
                    $avgChunkTime = $chunkCount > 0 ? round($totalChunkTime / $chunkCount, 2) : 0;
                    logger()->debug("{$exportName}: Export summary", [
                        'total_records' => $totalRecords,
                        'chunks_processed' => $chunkCount,
                        'average_chunk_time_ms' => $avgChunkTime,
                        'total_processing_time_ms' => $totalChunkTime
                    ]);
                }
            } else {
                // Fallback to less efficient memory approach if query builder not available
                $data = $this->collection($requestParams);
                logger()->debug("Using regular collection method - may use more memory");

                foreach ($data as $record) {
                    fputcsv($stream, $this->map($record));
                    $totalRecords++;

                    // Free up memory every 100 records
                    if ($totalRecords % 100 === 0) {
                        $currentMemory = round(memory_get_usage(true) / 1024 / 1024, 2);
                        logger()->debug("Processed {$totalRecords} records. Memory: {$currentMemory}MB");
                    }
                }
            }

            logger()->info("Completed writing {$totalRecords} records to CSV");

            // Get CSV content
            rewind($stream);
            $csvContent = stream_get_contents($stream);
            fclose($stream);

            $currentDate = Carbon::now()->format('d-m-Y');

            // Get recipient name if available
            $recipientName = 'User';
            if (! empty($requestParams['recipientName'])) {
                $recipientName = $requestParams['recipientName'];
            } elseif (auth()->check() && $recipientEmail === auth()->user()->email) {
                $recipientName = auth()->user()->name;
            }

            // Get the data collection and size information
            $fileSize = round(strlen($csvContent) / 1024, 2); // Size in KB

            $emailParams = [
                'recipientName' => $recipientName,
                'exportTitle' => $requestParams['exportTitle'],
                'currentDate' => $currentDate,

                // Add date range parameters if they exist
                'dateRangeStart' => $requestParams['created_at_start'] ?? null,
                'dateRangeEnd' => $requestParams['created_at_end'] ?? null,

                'recordCount' => $totalRecords,
                'fileSize' => $fileSize,
                'systemName' => config('constants.MAIL_FROM_NAME', 'The System'),
            ];

            // Send email with attachment
            Mail::send(
                ['html' => 'ExportCSVMail'],
                $emailParams,
                function ($message) use ($emailSubject, $recipientEmail, $ccRecipients, $fromName, $fromEmail, $csvContent, $csvFileName) {
                    $message->to($recipientEmail);

                    if (! empty($ccRecipients)) {
                        $message->cc($ccRecipients);
                    }

                    $message->subject($emailSubject);
                    $message->from($fromEmail, $fromName);

                    // Attach the CSV file
                    $message->attachData($csvContent, $csvFileName, [
                        'mime' => 'text/csv',
                    ]);
                }
            );

            // Clean up
            gc_collect_cycles();

            $finalMemory = round(memory_get_usage(true) / 1024 / 1024, 2);
            $peakMemory = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
            logger()->info("CSV export completed. Records: {$totalRecords}, Final memory: {$finalMemory}MB, Peak memory: {$peakMemory}MB");

        } catch (\Throwable $e) {
            logger()->error("Error in CSV export: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
