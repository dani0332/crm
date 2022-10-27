<?php

namespace App\Jobs;

use App\Models\RenewalQuoteProcess;
use App\Services\RenewalsUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessValidatedRenewal //implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $renewalQuoteProcess;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(RenewalQuoteProcess $renewalQuoteProcess)
    {
        $this->renewalQuoteProcess = $renewalQuoteProcess;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RenewalsUploadService $renewalsUploadService)
    {
        $renewalsUploadService->createQuote($this->renewalQuoteProcess);
    }
}
