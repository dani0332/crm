<?php

namespace App\Jobs\Renewals;

use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsBatchEmails;
use App\Services\EmailServices\HomeEmailService;
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

class HomeRenewalBatchEmailJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batchLeadId;
    protected $batchEmailId;
    protected $quoteTypeId;
    protected $isCompleted;
    protected $batch;
    protected $renewalsBatchEmailId;
    protected $renewalQuoteProcessId;
    public $tries = 3;
    public $timeout = 80;
    public $backoff = 360;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    // $batchLeadId, $batchEmailId, $quoteTypeId, $isCompleted, $batch
    public function __construct(int $batch, int $renewalsBatchEmailId, int $renewalQuoteProcessId)
    {

        $this->batch = $batch;
        $this->renewalsBatchEmailId = $renewalsBatchEmailId;
        $this->renewalQuoteProcessId = $renewalQuoteProcessId;
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
            'renewalQuoteProcessId' => $this->renewalQuoteProcessId,
        ]);

        $renewalQuoteProcess = RenewalQuoteProcess::find($this->renewalQuoteProcessId);
        $renewalsBatchEmail = RenewalsBatchEmails::find($this->renewalsBatchEmailId);

        $homeEmailService->sendRenewalOCBEmail($renewalsBatchEmail, $renewalQuoteProcess);

        LoggerService::info('Renewals OCB email job completed', extra: [
            'renewalQuoteProcessId' => $this->renewalQuoteProcessId,
        ]);
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->renewalQuoteProcessId))->dontRelease()];
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {
        LoggerService::error('CL: '.get_class().' FN: failed. Job Failed.', extra: [
            'renewalQuoteProcessId' => $this->renewalQuoteProcessId,
            'exception' => $exception->getMessage(),
        ]);
        RenewalsBatchEmails::where('id', $this->renewalsBatchEmailId)->update(['total_failed' => DB::raw('total_failed+1')]);
    }
}
