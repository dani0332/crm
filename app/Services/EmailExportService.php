<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CsvExportableInterface;
use App\Enums\EnvEnum;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
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
        // Generate CSV file
        $csvFilePath = $this->csvExportService->generateCsvFile($exporter, $requestParams);

        try {
            $this->sendEmailWithAttachment(
                $csvFilePath,
                $recipientEmail,
                $subject,
                $requestParams,
                $ccRecipients
            );
        } finally {
            // Always cleanup the file
            $this->csvExportService->cleanupFile($csvFilePath);
        }
    }

    /**
     * Send email with CSV attachment
     */
    private function sendEmailWithAttachment(
        string $csvFilePath,
        string $recipientEmail,
        string $subject,
        array $requestParams,
        array $ccRecipients
    ): void {
        $emailConfig = $this->getEmailConfiguration($subject);
        $emailParams = $this->buildEmailParameters($csvFilePath, $requestParams);

        Mail::send(
            ['html' => 'ExportCSVMail'],
            $emailParams,
            function ($message) use ($emailConfig, $recipientEmail, $ccRecipients, $csvFilePath) {
                $message->to($recipientEmail);

                if (! empty($ccRecipients)) {
                    $message->cc($ccRecipients);
                }

                $message->subject($emailConfig['subject']);
                $message->from($emailConfig['fromEmail'], $emailConfig['fromName']);

                // Attach CSV file
                $message->attach($csvFilePath, [
                    'as' => basename($csvFilePath),
                    'mime' => 'text/csv',
                ]);
            }
        );
    }

    /**
     * Get email configuration based on environment
     */
    private function getEmailConfiguration(string $subject): array
    {
        $emailEnv = config('constants.APP_ENV');

        if ($emailEnv === EnvEnum::PRODUCTION) {
            $fromEmail = config('constants.MAIL_FROM_ADDRESS_AML', config('constants.MAIL_FROM_ADDRESS'));
            $fromName = config('constants.MAIL_FROM_NAME_AML', config('constants.MAIL_FROM_NAME'));
            $finalSubject = $subject;
        } else {
            $fromEmail = config('constants.MAIL_FROM_ADDRESS');
            $fromName = config('constants.MAIL_FROM_NAME');
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
    private function buildEmailParameters(string $csvFilePath, array $requestParams): array
    {
        $recipientName = $this->getRecipientName($requestParams);
        $fileSize = $this->csvExportService->getFileSizeInKb($csvFilePath);

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
