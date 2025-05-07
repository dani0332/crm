<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExportCsvAndSendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $backoff = 30;
    private $exportClass;
    private $recipientEmail;
    private $requestParams;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $exportClass,
        string $recipientEmail,
        array $requestParams,

    ) {
        $this->exportClass = $exportClass;
        $this->recipientEmail = $recipientEmail;
        $this->requestParams = $requestParams;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info('CSV export job started for '.$this->requestParams['fileName'].' attempt: '.$this->attempts());

        try {
            DB::setDefaultConnection('mysql_read');

            // Instantiate the export class that uses the ExcelExportable trait
            $exportInstance = app($this->exportClass);

            // Use the existing trait method to handle the email with CSV attachment
            // sendEmailWithCSVAttachment(recipientEmail, emailSubject,  requestParams, ccRecipients = [], fileName = 'export')
            $exportInstance->sendEmailWithCSVAttachment(
                $this->requestParams['recipientEmail'],
                $this->requestParams['subject'],
                $this->requestParams,
                [],
                $this->requestParams['fileName'],
            );

            Log::info('CSV export job completed for '.$this->requestParams['fileName']);
            DB::setDefaultConnection('mysql');
            Auth::logout();
        } catch (\Throwable $e) {
            Log::error('CSV export job failed for '.$this->requestParams['fileName'].' attempt: '.$this->attempts().' Exception: '.$e->getMessage().', '.$e->getFile().':'.$e->getLine(), [
                'trace' => collect($e->getTrace())->filter(function ($trace) {
                    return $trace;
                    // return isset($trace['file']) && str_contains($trace['file'], '/app');
                })->all(),
            ]);
            DB::setDefaultConnection('mysql');
            Auth::logout();

            if ($this->attempts() >= $this->tries) {
                throw $e; // Throw Exception ONLY after all tries have failed
            } else {
                $this->release(60); // Retry after 60 seconds
            }
        }
    }
}
