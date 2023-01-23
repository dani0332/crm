<?php

namespace App\Jobs\Renewals;

use App\Services\RenewalsUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

class RenewalsQuoteAmlJob implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Stackable;

    protected $renewalQuoteProcess;
    protected $jobNo = null;
    public $timeout = 30;
    public $backoff = 10;
    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($renewalQuoteProcess, $jobNo)
    {
        $this->renewalQuoteProcess = $renewalQuoteProcess;
        $this->jobNo = $jobNo;
    }

    /**
     * @return array
     */
    public function middleware()
    {
        return [(new WithoutOverlapping($this->renewalQuoteProcess->id))->dontRelease()];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RenewalsUploadService $renewalsUploadService)
    {
        $logPrefix = 'fn: handle. class: RenewalsQuoteAmlJob ';
        info($logPrefix.' aml check for non-motor started jobNo: '.$this->jobNo.' quoteId: '.$this->renewalQuoteProcess->quote_id);
        $renewalsUploadService->checkAml($this->renewalQuoteProcess);
        info($logPrefix.' aml check for non-motor completed jobNo: '.$this->jobNo.' quoteId: '.$this->renewalQuoteProcess->quote_id);
    }

    /**
     * @param  Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        info('CL: RenewalsQuoteAmlJob FN: failed. Job Failed. renewalQuoteProcessId: '.$this->renewalQuoteProcess->id.' jobNo: '.$this->jobNo.' Error: '.$exception->getMessage());
    }
}
