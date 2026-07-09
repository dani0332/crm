<?php

namespace App\Jobs\Renewals;

use App\Exceptions\RenewalProcessException;
use App\Services\Logger\LoggerService;
use App\Services\RenewalsUploadService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class UpdateRenewalQuotesJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $backoff = 10;
    public $tries = 3;
    protected $renewalQuoteProcess;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($renewalQuoteProcess)
    {
        $this->delay = now()->addSeconds(2);
        $this->renewalQuoteProcess = $renewalQuoteProcess;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RenewalsUploadService $renewalsUploadService)
    {
        try {
            $renewalsUploadService->updateQuote($this->renewalQuoteProcess);
        } catch (Throwable $e) {
            throw $e; // Re-throw to mark job as failed
        }
    }

    /**
     * @return array
     */
    public function middleware()
    {
        return [(new WithoutOverlapping($this->renewalQuoteProcess->id))->dontRelease()->expireAfter($this->timeout)];
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

        LoggerService::error('CL: '.get_class().' FN: failed. Job Failed.', extra: [
            'renewalQuoteProcessId' => $this->renewalQuoteProcess->id,
            'step' => $step,
            'errors' => $errors,
        ], exception: $exception);

        $renewalsUploadService->updateRenewalQuoteProcess(
            $this->renewalQuoteProcess,
            true,
            $errors,
            $step
        );
    }
}
