<?php

namespace App\Jobs\Renewals;

use App\Models\RenewalQuoteProcess;
use App\Models\RenewalStatusProcess;
use App\Services\Logger\LoggerService;
use App\Services\RenewalsUploadService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Throwable;

class FetchPlansForHomeRenewalsQuoteJob implements ShouldQueue, StackableJob
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels,Stackable;

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
    public function __construct(RenewalQuoteProcess $renewalQuoteProcess, RenewalStatusProcess $renewalStatusProcess)
    {
        LoggerService::info('FetchPlansForHomeRenewalsQuoteJob: inside constructor', extra: [
            'renewalQuoteProcessId' => $renewalQuoteProcess->id,
        ]);
        $this->renewalQuoteProcess = $renewalQuoteProcess;
        $this->renewalStatusProcess = $renewalStatusProcess;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RenewalsUploadService $renewalsUploadService)
    {
        LoggerService::info('FetchPlansForHomeRenewalsQuoteJob: job being started', extra: [
            'renewalQuoteProcessId' => $this->renewalQuoteProcess->id,
            'policy_number' => $this->renewalQuoteProcess->policy_number,
        ]);
        $renewalsUploadService->fetchHomeQuotePlans($this->renewalQuoteProcess, $this->renewalStatusProcess);
    }

    /**
     * @return array
     */
    public function middleware()
    {
        return [(new WithoutOverlapping($this->renewalQuoteProcess->id))->dontRelease()];
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {

        LoggerService::error('CL: '.get_class().' FN: failed. Job Failed. '.$exception->getMessage(). ' Line: '.$exception->getLine(), extra: [
            'renewalQuoteProcessId' => $this->renewalQuoteProcess->id,
            'exception' => $exception->getMessage(),
        ]);
        RenewalStatusProcess::where('id', $this->renewalStatusProcess->id)->update(['total_failed' => DB::raw('total_failed+1')]);
    }
}
