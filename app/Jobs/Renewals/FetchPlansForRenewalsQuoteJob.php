<?php

namespace App\Jobs\Renewals;

use App\Models\RenewalQuoteProcess;
use App\Models\RenewalStatusProcess;
use App\Services\RenewalsUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchPlansForRenewalsQuoteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $renewalQuoteProcess;
    protected $renewalStatusProcess;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(RenewalQuoteProcess $renewalQuoteProcess, RenewalStatusProcess $renewalStatusProcess)
    {
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
        $renewalsUploadService->fetchQuotePlans($this->renewalQuoteProcess, $this->renewalStatusProcess);
    }
}
