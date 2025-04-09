<?php

namespace App\Traits;

use App\Enums\EnvEnum;
use App\Jobs\ExportCsvAndSendEmailJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait ExcelExportable
{
    abstract public function collection($requestParams);
    abstract public function headings();
    abstract public function map($quote);

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

    public function emailCSV($fileName, $requestParams = [])
    {
        $fileName = $fileName.'-'.Carbon::now()->format('Y-m-d');

        if (empty($requestParams['recipientEmail'])) {
            // To will be currentUserID
            if (auth()->check()) {
                $currentUserEmailId = User::where('id', '=', auth()->user()->id)->value('email');
                $requestParams['recipientEmail'] = $currentUserEmailId;
            } else {
                return response()->json([
                    'error' => 'User not authenticated',
                    'message' => 'Cannot send email as the user is not authenticated.',
                ], 401);
            }
        }

        $currentDate = Carbon::now()->format('d-m-Y');
        $requestParams['quoteType'] = ucfirst($requestParams['quoteType']);
        $requestParams['fileName'] = $fileName;
        $requestParams['subject'] = "{$requestParams['quoteType']} Export - {$currentDate}";

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
     * Send email with CSV data as attachment using AML service style
     *
     * @param  string  $templateName  Email template name
     * @param  array  $requestParams  Parameters for the email template
     * @param  string  $emailSubject  Email subject
     * @param  string|array  $recipientEmail  Recipient email(s)
     * @param  array  $ccRecipients  CC recipients
     * @param  string  $fileName  Filename for the CSV attachment (without extension)
     * @return void
     */
    public function sendEmailWithCSVAttachment($recipientEmail, $emailSubject, $requestParams, $ccRecipients = [], $fileName = 'export')
    {
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

        // Generate CSV content in memory
        $csvFileName = $fileName.'-'.Carbon::now()->format('Y-m-d').'.csv';
        $stream = fopen('php://temp', 'r+');

        $requestParams['user'] = User::where(['email' => $requestParams['recipientEmail']])->first();

        // Write CSV data
        fputcsv($stream, $this->headings());
        $data = $this->collection($requestParams);
        foreach ($data as $quote) {
            fputcsv($stream, $this->map($quote));
        }

        // Get CSV content
        rewind($stream);
        $csvContent = stream_get_contents($stream);
        fclose($stream);

        $currentDate = Carbon::now()->format('d-m-Y');

        // Get recipient name if available
        $recipientName = 'User';
        if (! empty($requestParams['recipient_name'])) {
            $recipientName = $requestParams['recipient_name'];
        } elseif (auth()->check() && $recipientEmail === auth()->user()->email) {
            $recipientName = auth()->user()->name;
        }

        // Get the data collection and size information
        $recordCount = count($data);
        $fileSize = round(strlen($csvContent) / 1024, 2); // Size in KB

        $emailParams = [
            'recipientName' => $recipientName,
            'quoteTypeName' => $requestParams['quoteType'],
            'currentDate' => $currentDate,
            'recordCount' => $recordCount,
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

    }
}
