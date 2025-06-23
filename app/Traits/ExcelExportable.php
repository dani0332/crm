<?php

namespace App\Traits;

use App\Enums\EnvEnum;
use App\Jobs\ExportCsvAndSendEmailJob;
use App\Models\User;
use App\Services\Logger\LoggerService;
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
        // info('Dispatched ExportCsvAndSendEmailJob');

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
        // Set read database connection for export operations
        DB::setDefaultConnection('mysql_read');

        try {
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
                $emailSubject = $emailL_sys.' - '.$emailSubject;
            }

            // Generate CSV content in memory using chunking
            $csvFileName = $fileName.'.csv';
            $csvFilePath = storage_path('temp/'.$csvFileName); // Temporary file path

            $stream = fopen($csvFilePath, 'w'); // Open file on disk for writing

            $requestParams['user'] = User::where(['email' => $requestParams['recipientEmail']])->first();

            // Write CSV headers
            fputcsv($stream, $this->headings());

            $startDate = isset($requestParams['created_at_start']) ? Carbon::parse($requestParams['created_at_start'])->format('d M Y') : '';
            $endDate = isset($requestParams['created_at_end']) ? Carbon::parse($requestParams['created_at_end'])->format('d M Y') : '';

            if ($startDate && $endDate) {
                $diff = abs(Carbon::parse($requestParams['created_at_start'])->diffInDays(Carbon::parse($requestParams['created_at_end']))) + 1;
                logger()->debug("Processing export between: {$startDate} - {$endDate} ({$diff} days)");
            }

            $totalRecords = 0;
            $chunkSize = 1000; // Adjust based on your data complexity
            $exportName = class_basename($this);
            $chunkCount = 0;
            $totalChunkTime = 0;

            // Use the query builder version of collection if available
            if (method_exists($this, 'getQuery')) {
                // Process data in memory-efficient chunks
                LoggerService::info('Starting CSV export with chunking');
                $query = $this->getQuery($requestParams);

                // Use database chunking for efficient memory usage
                $query->chunk($chunkSize, function ($records) use ($stream, &$totalRecords, &$chunkCount, &$totalChunkTime) {
                    $chunkStartTime = microtime(true);
                    $chunkCount++;

                    // Track memory before mapping records
                    $memoryBeforeMapping = round(memory_get_usage(true) / 1024 / 1024, 2);

                    foreach ($records as $record) {
                        $mappedRow = $this->map($record);
                        fputcsv($stream, $mappedRow);
                        $totalRecords++;
                    }

                    $currentMemory = round(memory_get_usage(true) / 1024 / 1024, 2);
                    $memoryDiff = $currentMemory - $memoryBeforeMapping;
                    $chunkTime = round((microtime(true) - $chunkStartTime) * 1000, 2); // in milliseconds
                    $totalChunkTime += $chunkTime;

                    // logger()->debug("{$exportName}: Chunk #{$chunkCount} processed. Memory: {$currentMemory}MB, Memory diff: {$memoryDiff}MB, Chunk Time: {$chunkTime}ms, Total time: {$totalChunkTime}ms");

                    // Force garbage collection to free memory
                    gc_collect_cycles();
                });

            } else {
                // Fallback to less efficient memory approach if query builder not available
                $data = $this->collection($requestParams);
                LoggerService::info('Using regular collection method - may use more memory');

                foreach ($data as $record) {
                    fputcsv($stream, $this->map($record));
                    $totalRecords++;

                    // Free up memory every 100 records
                    if ($totalRecords % 100 === 0) {
                        $currentMemory = round(memory_get_usage(true) / 1024 / 1024, 2);
                        // logger()->debug("Processed {$totalRecords} records. Memory: {$currentMemory}MB");
                    }
                }
            }

            // Close the file stream
            fclose($stream);

            $currentDate = Carbon::now()->format('d-m-Y');

            // Get recipient name if available
            $recipientName = 'User';
            if (! empty($requestParams['recipientName'])) {
                $recipientName = $requestParams['recipientName'];
            } elseif (auth()->check() && $recipientEmail === auth()->user()->email) {
                $recipientName = auth()->user()->name;
            }

            // Get the file size
            $fileSize = round(filesize($csvFilePath) / 1024, 2); // Size in KB

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
                function ($message) use ($emailSubject, $recipientEmail, $ccRecipients, $fromName, $fromEmail, $csvFilePath, $csvFileName) {
                    $message->to($recipientEmail);

                    if (! empty($ccRecipients)) {
                        $message->cc($ccRecipients);
                    }

                    $message->subject($emailSubject);
                    $message->from($fromEmail, $fromName);

                    // Attach the CSV file from disk
                    $message->attach($csvFilePath, [
                        'as' => $csvFileName,
                        'mime' => 'text/csv',
                    ]);
                }
            );

            // Clean up the temporary file
            if (file_exists($csvFilePath)) {
                unlink($csvFilePath);
            }

            // Clean up
            gc_collect_cycles();

            $finalMemory = round(memory_get_usage(true) / 1024 / 1024, 2);
            $peakMemory = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
            LoggerService::info("CSV export completed. Records: {$totalRecords}, Final memory: {$finalMemory}MB, Peak memory: {$peakMemory}MB");

        } catch (\Throwable $e) {
            // Clean up the file in case of an error
            if (file_exists($csvFilePath)) {
                unlink($csvFilePath);
            }
            LoggerService::error('Error in CSV export: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        } finally {
            // Always reset database connection back to default
            DB::setDefaultConnection('mysql');
        }
    }
}
