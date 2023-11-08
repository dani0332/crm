<?php

namespace App\Jobs\Renewals;

use App\Enums\RenewalProcessStatuses;
use App\Models\RenewalsUploadLeads;
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

class CreateTravelRenewalQuotesJob implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Stackable;

    public $timeout = 30;
    public $backoff = 90;
    public $tries = 3;
    private $renewalQuoteProcess;

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
        $renewalsUploadService->createTravelProcessQuote($this->renewalQuoteProcess);
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
        $this->renewalQuoteProcess->update(['status' => RenewalProcessStatuses::FAILED]);
        RenewalsUploadLeads::where('id', $this->renewalQuoteProcess->renewals_upload_lead_id)->update(['cannot_upload' => DB::raw('cannot_upload+1')]);
    }
}
