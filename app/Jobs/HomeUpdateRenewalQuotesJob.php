<?php

namespace App\Jobs;

use App\Enums\RenewalProcessStatuses;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
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

class HomeUpdateRenewalQuotesJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable,SerializesModels;

    public $timeout = 60;
    public $backoff = 10;
    public $tries = 3;
    protected $renewalQuoteProcessId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $renewalQuoteProcessId)
    {
        $this->renewalQuoteProcessId = $renewalQuoteProcessId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(HomeRenewalService $homeRenewalService)
    {

        $homeRenewalService->updateQuoteHome($this->renewalQuoteProcessId);
    }

    /**
     * @return array
     */
    public function middleware()
    {
        return [(new WithoutOverlapping($this->renewalQuoteProcessId))->dontRelease()];
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {
        LoggerService::error('CL: '.get_class().' FN: failed. Job Failed. Error: '.$exception->getMessage(), extra: [
            'renewalQuoteProcessId' => $this->renewalQuoteProcessId,
        ]);

        $renewalQuoteProcess = RenewalQuoteProcess::where('id', $this->renewalQuoteProcessId)->select('renewals_upload_lead_id', 'id')->first();
        $renewalQuoteProcess->update(['status' => RenewalProcessStatuses::FAILED]);
        RenewalsUploadLeads::where('id', $renewalQuoteProcess->renewals_upload_lead_id)->update(['cannot_upload' => DB::raw('cannot_upload+1')]);
    }
}
