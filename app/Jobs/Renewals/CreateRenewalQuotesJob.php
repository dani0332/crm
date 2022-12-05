<?php

namespace App\Jobs\Renewals;

use App\Services\RenewalsUploadService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class CreateRenewalQuotesJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $backoff = 65;
    public $tries = 3;
    protected $renewalQuoteProcess;
    protected $uniqueId = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($renewalQuoteProcess)
    {
        $this->renewalQuoteProcess = $renewalQuoteProcess;
        $this->uniqueId = Str::random(4).rand(0, 200000);
        $this->onQueue('renewals');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RenewalsUploadService $renewalsUploadService)
    {
        foreach ($this->renewalQuoteProcess as $lead) {
            $renewalsUploadService->createQuote($lead);
        }
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->uniqueId))->dontRelease()];
    }
}
