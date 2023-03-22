<?php

namespace App\Jobs\Renewals;

use App\Enums\ProcessStatusCode;
use App\Models\RenewalStatusProcess;
use App\Services\RenewalsUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class FetchRenewalsPlansJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 1200;
    public $backoff = 10;
    protected $batch;
    protected $renewalStatusProcess;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(RenewalStatusProcess $renewalStatusProcess, $batch)
    {
        $this->batch = $batch;
        $this->renewalStatusProcess = $renewalStatusProcess;
        $this->onQueue('renewals');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RenewalsUploadService $renewalsUploadService)
    {
        $renewalsUploadService->fetchRenewalPlans($this->renewalStatusProcess, $this->batch);
    }

    /**
     * @return array
     */
    public function middleware()
    {
        return [(new WithoutOverlapping($this->renewalStatusProcess->id))->dontRelease()];
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {
        info('CL: '.get_class().' FN: failed. Job Failed. renewalStatusProcessId: '.$this->renewalStatusProcess->id.' Error: '.$exception->getMessage());
        RenewalStatusProcess::where('id', $this->renewalStatusProcess->id)->update(['status' => ProcessStatusCode::FAILED]);
    }
}
