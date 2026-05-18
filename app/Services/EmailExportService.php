<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CsvExportableInterface;
use App\Enums\EnvEnum;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailExportService
{
    public function __construct(
        private CsvExportService $csvExportService
    ) {}

    /**
     * Generate CSV and send as email attachment
     */
    public function sendCsvByEmail(
        CsvExportableInterface $exporter,
        string $recipientEmail,
        string $subject,
        array $requestParams = [],
        array $ccRecipients = []
    ): void {
        // Generate CSV file and get record count
        $csvResult = $this->csvExportService->generateCsvFileWithCount($exporter, $requestParams);
        $csvFilePath = $csvResult['filePath'];
        $requestParams['recordCount'] = $csvResult['recordCount'];

        // Create zip file containing the CSV
        $zipFilePath = $this->createZipFile($csvFilePath);

        try {
            $this->sendEmailWithAttachment(
                $zipFilePath,
                $recipientEmail,
                $subject,
                $requestParams,
                $ccRecipients
            );
        } finally {
            // Always cleanup both files
            $this->csvExportService->cleanupFile($csvFilePath);
            $this->cleanupZipFile($zipFilePath);
        }
    }

    /**
     * Create a zip file containing the CSV file
     */
    private function createZipFile(string $csvFilePath): string
    {
        $zipFileName = pathinfo($csvFilePath, PATHINFO_FILENAME).'.zip';
        $zipFilePath = storage_path('temp/'.$zipFileName);

        // Ensure temp directory exists
        if (! is_dir(dirname($zipFilePath))) {
            mkdir(dirname($zipFilePath), 0755, true);
        }

        $zip = new \ZipArchive;

        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Cannot create zip file: '.$zipFilePath);
        }

        // Add CSV file to zip with just the filename (no path)
        $zip->addFile($csvFilePath, basename($csvFilePath));
        $zip->close();

        return $zipFilePath;
    }

    /**
     * Clean up zip file
     */
    private function cleanupZipFile(string $zipFilePath): void
    {
        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }
    }

    /**
     * Send email with CSV attachment
     */
    private function sendEmailWithAttachment(
        string $attachmentFilePath,
        string $recipientEmail,
        string $subject,
        array $requestParams,
        array $ccRecipients
    ): void {
        $emailConfig = $this->getEmailConfiguration($subject);
        $emailParams = $this->buildEmailParameters($attachmentFilePath, $requestParams);

        try {
            Mail::mailer()->getSymfonyTransport()->stop();

            Mail::send(
                ['html' => 'ExportCSVMail'],
                $emailParams,
                function ($message) use ($emailConfig, $recipientEmail, $ccRecipients, $attachmentFilePath) {
                    $message->to($recipientEmail);

                    if (! empty($ccRecipients)) {
                        $message->cc($ccRecipients);
                    }

                    $message->subject($emailConfig['subject']);
                    $message->from($emailConfig['fromEmail'], $emailConfig['fromName']);

                    // Attach file with appropriate MIME type
                    $mimeType = $this->getMimeType($attachmentFilePath);
                    $message->attach($attachmentFilePath, [
                        'as' => basename($attachmentFilePath),
                        'mime' => $mimeType,
                    ]);
                }
            );

            // Log successful email send
            Log::info('Export email sent successfully', [
                'recipient' => $recipientEmail,
                'subject' => $emailConfig['subject'],
                'file' => basename($attachmentFilePath),
            ]);
        } catch (\Throwable $e) {
            // Log email sending failure
            Log::error('Failed to send export email', [
                'recipient' => $recipientEmail,
                'subject' => $emailConfig['subject'],
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // Re-throw exception so job can handle it properly
            throw new \Exception("Failed to send email to {$recipientEmail}: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Get MIME type based on file extension
     */
    private function getMimeType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'zip' => 'application/zip',
            'csv' => 'text/csv',
            default => 'application/octet-stream'
        };
    }

    /**
     * Get email configuration based on environment
     */
    private function getEmailConfiguration(string $subject): array
    {
        $emailEnv = config('constants.APP_ENV');

        $fromEmail = config('constants.MAIL_FROM_ADDRESS');
        $fromName = config('constants.MAIL_FROM_NAME');

        if ($emailEnv === EnvEnum::PRODUCTION) {
            $finalSubject = $subject;
        } else {
            $finalSubject = $emailEnv.' - '.$subject;
        }

        return [
            'fromEmail' => $fromEmail,
            'fromName' => $fromName,
            'subject' => $finalSubject,
        ];
    }

    /**
     * Build email template parameters
     */
    private function buildEmailParameters(string $attachmentFilePath, array $requestParams): array
    {
        $recipientName = $this->getRecipientName($requestParams);
        $fileSize = $this->csvExportService->getFileSizeInKb($attachmentFilePath);

        return [
            'recipientName' => $recipientName,
            'exportTitle' => $requestParams['exportTitle'] ?? 'Data',
            'currentDate' => Carbon::now()->format('d-m-Y'),
            'dateRangeStart' => $requestParams['created_at_start'] ?? null,
            'dateRangeEnd' => $requestParams['created_at_end'] ?? null,
            'recordCount' => $requestParams['recordCount'] ?? 0,
            'fileSize' => $fileSize,
            'systemName' => config('constants.MAIL_FROM_NAME', 'The System'),
        ];
    }

    /**
     * Get recipient name from request params or user lookup
     */
    private function getRecipientName(array $requestParams): string
    {
        // Check request params first
        if (! empty($requestParams['recipientName'])) {
            return $requestParams['recipientName'];
        }

        // Try to get from user by email (jobs don't have session auth)
        if (isset($requestParams['recipientEmail'])) {
            $user = User::where('email', $requestParams['recipientEmail'])->first();
            if ($user) {
                return $user->name;
            }
        }

        return 'User';
    }
}
