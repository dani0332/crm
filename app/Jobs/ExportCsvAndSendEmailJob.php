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

    public $timeout = 300; // 300 (5 minutes) 900 (15 minutes)
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
        $jobId = $this->job->getJobId() ?? 'unknown';
        $startTime = microtime(true);

        Log::info("CSV export job [{$jobId}]  started for ".$this->requestParams['fileName'].' attempt: '.$this->attempts());

//        try {
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

            $executionTime = round(microtime(true) - $startTime, 2);
            Log::info("CSV export job [{$jobId}] completed successfully in {$executionTime}s");
//        } catch (\Throwable $e) {
//            $executionTime = round(microtime(true) - $startTime, 2);
//
//            Log::error("CSV export job failed in {$executionTime}s for ".$this->requestParams['fileName'].'. attempt: '.$this->attempts().' Exception: '.$e->getMessage().', '.$e->getFile().':'.$e->getLine(), [
//                'trace' => collect($e->getTrace())->filter(function ($trace) {
//                    return isset($trace['file']) && str_contains($trace['file'], '/app');
//                })->all(),
//            ]);
//
//            // Only retry if we haven't exceeded the maximum attempts
//            if ($this->attempts() < $this->tries) {
//                logger()->error("CSV export job failed: Attempts ({$this->attempts()}) <  tries ($this->tries) releasing it back after {$this->backoff} seconds.");
//                // Release back to queue for retry after backoff period
//                $this->release($this->backoff);
//                return;
//            }
//
//            throw $e; // Throw the exception after all retries have failed
//        } finally {
            // Always clean up connections regardless of success or failure
            DB::setDefaultConnection('mysql');
            Auth::logout();
//        }
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception)
    {
        // This is called when the job fails completely
        Log::error("CSV export job for {$this->requestParams['fileName']} has permanently failed: {$exception->getMessage()}");
    }
}
