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

class CreateRenewalQuotesJob implements ShouldQueue, StackableJob
{
    use Stackable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

    public function middleware()
    {
        return [(new WithoutOverlapping($this->renewalQuoteProcess->id))->dontRelease()];
    }
}
