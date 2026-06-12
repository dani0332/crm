<?php

namespace App\Jobs\Renewals;

use App\Exceptions\RenewalProcessException;
use App\Services\Logger\LoggerService;
use App\Services\RenewalsUploadService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RetryHealthRenewalProcess implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180;
    public $backoff = 10;
    public $tries = 3;
    protected $renewalQuoteProcess;

    /**
     * Create a new job instance.
     */
    public function __construct($renewalQuoteProcess)
    {
        $this->delay = now()->addSeconds(2);
        $this->renewalQuoteProcess = $renewalQuoteProcess;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $renewalUploadService = app(RenewalsUploadService::class);
            $renewalUploadService->retryRenewalQuoteProcess($this->renewalQuoteProcess);
        } catch (Throwable $th) {
            throw $th;
        }
    }

    /**
     * @return array
     */
    public function middleware()
    {
        return [(new WithoutOverlapping($this->renewalQuoteProcess->id))->dontRelease()];
    }

    /**
     * Handle a job failure.
     *
     * @return void
     */
    public function failed(Throwable $exception)
    {

        $renewalsUploadService = app(RenewalsUploadService::class);
        $errors = [];
        $step = null;

        if ($exception instanceof RenewalProcessException) {
            $errors = $exception->getErrors();
            $step = $exception->getStep();
        } else {
            $errors = ['Unexpected error: '.$exception->getMessage()];
        }

        LoggerService::error('Retry Health Renewal Process Failed', [
            'quote_process_id' => $this->renewalQuoteProcess->id,
            'step' => $step,
            'errors' => $errors,
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $renewalsUploadService->updateRenewalQuoteProcess(
            $this->renewalQuoteProcess,
            true,
            $errors,
            $step
        );
    }
}
