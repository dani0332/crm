<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExportCsvAndSendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $backoff = 30;

    protected $exportClass;
    protected $recipientEmail;
    protected $requestParams;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $exportClass,
        string $recipientEmail,
        array  $requestParams,

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
        Log::info('CSV export job started for ' . $this->requestParams['fileName'] . ' attempt: ' . $this->attempts());

        info("this->exportClass: ".$this->exportClass);
        try {
            // Instantiate the export class that uses the ExcelExportable trait
            $exportInstance = app($this->exportClass);





            //<editor-fold desc="Demo work">
            // Demo associative array
            $demoArray = [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'age' => 30,
                'role' => 'Developer'
            ];

            // Create a new Request instance and populate it
            $demoRequest = Request::createFromBase(new \Symfony\Component\HttpFoundation\Request());
            $demoRequest->request->add($demoArray); // Add data to the "post" bag

            // Log to verify direct property access
            info("Demo Request Test: " . print_r([
                    'request' => $demoRequest->all(),           // All input data
                    'name_property' => $demoRequest->name,      // Direct access: 'John Doe'
                    'email_property' => $demoRequest->email,    // Direct access: 'john.doe@example.com'
                    'age_property' => $demoRequest->age,        // Direct access: 30
                    'missing_property' => $demoRequest->missing // Direct access: null
                ], 1));
            //</editor-fold>






            // Use the existing trait method to handle the email with CSV attachment
            // sendEmailWithCSVAttachment(recipientEmail, emailSubject,  requestParams, ccRecipients = [], fileName = 'export')
            $exportInstance->sendEmailWithCSVAttachment(
                $this->requestParams['recipientEmail'],
                $this->requestParams['subject'],
                $this->requestParams,
                [],
                $this->requestParams['fileName'],
            );

            Log::info('CSV export job completed for ' . $this->requestParams['fileName']);
        } catch (\Throwable $e) {
            Log::error('CSV export job failed for ' . $this->requestParams['fileName'] . ' attempt: ' . $this->attempts() . ' Exception: ' . $e->getMessage().', '.$e->getFile().':'.$e->getLine(),[
                'trace' =>collect($e->getTrace())->filter(function ($trace) {
                    return $trace;
                    // return isset($trace['file']) && str_contains($trace['file'], '/app');
                })->all(),
            ]);

            if ($this->attempts() >= $this->tries) {
                throw $e; // Throw Exception ONLY after all tries have failed
            } else {
                $this->release(60); // Retry after 60 seconds
            }
        }
    }
}
