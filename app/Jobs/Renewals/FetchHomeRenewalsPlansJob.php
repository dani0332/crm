<?php

namespace App\Jobs\Renewals;

use App\Enums\ProcessStatusCode;
use App\Models\RenewalStatusProcess;
use App\Services\HomeRenewalService;
use App\Services\Logger\LoggerService;
use App\Services\RenewalsUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class FetchHomeRenewalsPlansJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 1200;
    public $backoff = 10;
    protected $batch;
    protected $quoteType;
    protected $renewalStatusProcess;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(RenewalStatusProcess $renewalStatusProcess, $batch, $quoteType)
    {
        $this->batch = $batch;
        $this->renewalStatusProcess = $renewalStatusProcess;
        $this->quoteType = $quoteType;
        $this->onQueue('renewals');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(HomeRenewalService $homeRenewalService)
    {
        LoggerService::info('FetchHomeRenewalsPlansJob: job being started', extra: [
            'renewalStatusProcessId' => $this->renewalStatusProcess->id,
            'batch' => $this->batch,
        ]);
        $homeRenewalService->fetchRenewalPlans($this->renewalStatusProcess, $this->batch);
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
        LoggerService::error('CL: '.get_class().' FN: failed. Job Failed.', extra: [
            'renewalStatusProcessId' => $this->renewalStatusProcess->id,
            'exception' => $exception->getMessage(),
        ]);
        RenewalStatusProcess::where('id', $this->renewalStatusProcess->id)->update(['status' => ProcessStatusCode::FAILED]);
    }
}
