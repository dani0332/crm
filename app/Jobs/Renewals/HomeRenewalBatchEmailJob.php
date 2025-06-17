<?php

namespace App\Jobs\Renewals;

use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsBatchEmails;
use App\Services\EmailServices\HomeEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Bus\Batchable;
use Throwable;

class HomeRenewalBatchEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    protected $batchLeadId;
    protected $batchEmailId;
    protected $quoteTypeId;
    protected $isCompleted;
    protected $batch;
    protected $renewalsBatchEmail;
    protected $renewalQuoteProcess;
    public $tries = 3;
    public $timeout = 80;
    public $backoff = 360;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    // $batchLeadId, $batchEmailId, $quoteTypeId, $isCompleted, $batch
    public function __construct($batch, RenewalsBatchEmails $renewalsBatchEmail, RenewalQuoteProcess $renewalQuoteProcess)
    {

        $this->batch = $batch;
        $this->renewalsBatchEmail = $renewalsBatchEmail;
        $this->renewalQuoteProcess = $renewalQuoteProcess;
        $this->onQueue('renewals');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(HomeEmailService $homeEmailService)
    {
        LoggerService::info('Renewals OCB email job started', extra: [
            'renewalQuoteProcessId' => $this->renewalQuoteProcess->id,
        ]);

        $homeEmailService->sendRenewalOCBEmail($this->renewalsBatchEmail, $this->renewalQuoteProcess);

        LoggerService::info('Renewals OCB email job completed', extra: [
            'renewalQuoteProcessId' => $this->renewalQuoteProcess->id,
        ]);
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->renewalQuoteProcess->id))->dontRelease()];
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {
        LoggerService::error('CL: '.get_class().' FN: failed. Job Failed.', extra: [
            'renewalQuoteProcessId' => $this->renewalQuoteProcess->id,
            'exception' => $exception->getMessage(),
        ]);
        RenewalsBatchEmails::where('id', $this->renewalsBatchEmail->id)->update(['total_failed' => DB::raw('total_failed+1')]);
    }
}
