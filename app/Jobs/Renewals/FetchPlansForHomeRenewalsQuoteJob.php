<?php

namespace App\Jobs\Renewals;

use App\Models\RenewalQuoteProcess;
use App\Models\RenewalStatusProcess;
use App\Services\HomeRenewalService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class FetchPlansForHomeRenewalsQuoteJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $renewalQuoteProcess;
    protected $renewalStatusProcess;
    public $timeout = 60;
    public $backoff = 10;
    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        LoggerService::info('FetchPlansForHomeRenewalsQuoteJob: inside constructor');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(HomeRenewalService $homeRenewalService, RenewalQuoteProcess $renewalQuoteProcess, RenewalStatusProcess $renewalStatusProcess)
    {
        LoggerService::info('FetchPlansForHomeRenewalsQuoteJob: job being started', extra: [
            'renewalQuoteProcessId' => $renewalQuoteProcess->id,
            'policy_number' => $renewalQuoteProcess->policy_number,
        ]);
        $homeRenewalService->fetchPlans($renewalQuoteProcess, $renewalStatusProcess);
    }

    /**
     * @return array
     */
    public function middleware()
    {
        // return [(new WithoutOverlapping($this->renewalQuoteProcess->id))->dontRelease()];
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {

        LoggerService::error('CL: '.get_class().' FN: failed. Job Failed. '.$exception->getMessage().' Line: '.$exception->getLine(), extra: [
            // 'renewalQuoteProcessId' => $this->renewalQuoteProcess->id,
            'exception' => $exception->getMessage(),
        ]);
        // RenewalStatusProcess::where('id', $this->renewalStatusProcess->id)->update(['total_failed' => DB::raw('total_failed+1')]);
    }
}
