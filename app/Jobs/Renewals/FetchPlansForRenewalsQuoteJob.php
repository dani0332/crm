<?php

namespace App\Jobs\Renewals;

use App\Models\RenewalQuoteProcess;
use App\Models\RenewalStatusProcess;
use App\Services\RenewalsUploadService;
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

class FetchPlansForRenewalsQuoteJob implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Stackable;

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
        info('FetchPlansForRenewalsQuoteJob: inside constructor');
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
        info('FetchPlansForRenewalsQuoteJob: job being started for policy_number: '.$this->renewalQuoteProcess->policy_number);
        $renewalsUploadService->fetchQuotePlans($this->renewalQuoteProcess, $this->renewalStatusProcess);
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
        info('CL: '.get_class().' FN: failed. Job Failed. renewalQuoteProcessId: '.$this->renewalQuoteProcess->id.' Error: '.$exception->getMessage());
        RenewalStatusProcess::where('id', $this->renewalStatusProcess->id)->update(['total_failed' => DB::raw('total_failed+1')]);
    }
}
