<?php

namespace App\Jobs;

use App\Models\RenewalStatusProcess;
use App\Services\RenewalsUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchRenewalsPlansJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;
    public $backoff = 35;
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
}
