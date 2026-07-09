<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EnvEnum;
use App\Enums\WorkflowTypeEnum;
use App\Exports\BranchOverrides\BranchOverrideDetailsExport;
use App\Models\ApplicationStorage;
use App\Services\EmailServices\WebEngageService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class MisReportJob implements ShouldQueue
{
    use GenericQueriesAllLobs, Queueable;

    public $timeout = 60;
    private $startDate;
    private $endDate;

    public function __construct()
    {
        $this->startDate = now()->subDays(7)->startOfDay();
        $this->endDate = now()->subDays(1)->endOfDay();
    }

    public function handle(): void
    {
        $enableMisreportJob = ApplicationStorage::where('key_name', ApplicationStorageEnums::ENABLE_MISREPORT_JOB)->first();
        if (! $enableMisreportJob || $enableMisreportJob->value == 0) {
            LoggerService::info('MisReportJob - Misreport job is disabled');

            return;
        }
        $startDateFormatted = $this->startDate->format(config('constants.DB_DATE_FORMAT_MATCH'));
        $endDateFormatted = $this->endDate->format(config('constants.DB_DATE_FORMAT_MATCH'));

        LoggerService::info('MisReportJob - Starting', extra: [
            'startDate' => $startDateFormatted,
            'endDate' => $endDateFormatted,
        ]);

        $this->sendEmail();

        LoggerService::info('MisReportJob - Completed', extra: [
            'startDate' => $startDateFormatted,
            'endDate' => $endDateFormatted,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        LoggerService::error('MisReportJob - Failed', exception: $exception, extra: [
            'startDate' => $this->startDate->format(config('constants.DB_DATE_FORMAT_MATCH')),
            'endDate' => $this->endDate->format(config('constants.DB_DATE_FORMAT_MATCH')),
        ]);
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        $lockKey = 'misreport-job';

        return [
            (new WithoutOverlapping($lockKey))
                ->dontRelease()
                ->expireAfter(120),
        ];
    }

    private function sendEmail()
    {
        $fileDate = now()->format(config('constants.MISREPORT_FILENAME_DATE_FORMAT'));
        $attachment = $this->getAttachment($fileDate);
        $reportDate = now()->format(config('constants.MISREPORT_SUBJECT_DATE_FORMAT'));
        $subject = "Interim AUH→DXB Override – Weekly MIS Report | <$reportDate>";
        $env = config('constants.APP_ENV');
        if ($env != EnvEnum::PRODUCTION) {
            $subject = "$env | $subject";
        }

        $emailData = [
            'subject' => $subject,
            'startDate' => $this->startDate->format(config('constants.MISREPORT_DATE_FORMAT')),
            'endDate' => $this->endDate->format(config('constants.MISREPORT_DATE_FORMAT')),
            'fileDate' => $fileDate,
            'misAttachmentFileName' => $attachment['fileName'],
            'misAttachmentUrl' => $attachment['fileUrl'],
            'workflowType' => WorkflowTypeEnum::SEND_MISREPORT_EMAIL,
            'Tags' => WorkflowTypeEnum::SEND_MISREPORT_EMAIL,
        ];

        $this->triggerWebEngageEvent($emailData);
    }

    /**
     * Generate and prepare Excel attachment for Bird email
     *
     * @param  string  $fileDate  Formatted date for the filename
     * @return array Array of attachments with Azure temporary URL
     */
    private function getAttachment(string $fileDate): array
    {
        $fileName = "Interim_AUH_DXB_Override_MIS_{$fileDate}.xlsx";

        // Generate Excel file content in memory
        $excelContent = Excel::raw(new BranchOverrideDetailsExport, \Maatwebsite\Excel\Excel::XLSX);

        LoggerService::info('MisReportJob - Excel file generated successfully');

        // Generate a unique temporary file path
        $tempFilePath = 'temp/misreport/'.uniqid().'_'.$fileName;
        Storage::disk('azureIM')->put($tempFilePath, $excelContent);

        LoggerService::info('MisReportJob - Excel file uploaded to Azure storage', extra: [
            'filePath' => $tempFilePath,
            'contentSize' => strlen($excelContent),
        ]);

        // Generate a public URL (expires in 60 minutes)
        // @phpstan-ignore-next-line
        $publicUrl = Storage::disk('azureIM')->temporaryUrl(
            $tempFilePath,
            now()->addMinutes(60)
        );

        $attachment = [
            'fileUrl' => $publicUrl,
            'fileName' => $fileName,
        ];

        LoggerService::info('MisReportJob - Temporary URL generated for attachment', extra: [
            'fileName' => $fileName,
            'url' => $publicUrl,
        ]);

        // Schedule file deletion after 60 minutes
        DeleteTempOCBPDFFileJob::dispatch($tempFilePath)->delay(now()->addMinutes(60));

        return $attachment;
    }

    private function triggerWebEngageEvent(array $emailData): void
    {
        $recipientEmail = getAppStorageValueByKey(ApplicationStorageEnums::MISREPORT_RECIPIENT_EMAIL);

        if (empty($recipientEmail)) {
            LoggerService::error('MisReportJob - MISREPORT_RECIPIENT_EMAIL not found in ApplicationStorage');

            return;
        }

        $payload = [
            'customerId' => $recipientEmail,
            'firstName' => '',
            'lastName' => '',
            'customerEmail' => $recipientEmail,
            'customerMobile' => '',
            'quoteUID' => '',
            ...$emailData,
        ];

        app(WebEngageService::class)->sendEvent(WorkflowTypeEnum::SEND_MISREPORT_EMAIL, $payload);
        LoggerService::info('MisReportJob - WebEngage event triggered successfully');
    }
}
