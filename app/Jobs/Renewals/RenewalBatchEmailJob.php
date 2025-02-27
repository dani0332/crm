<?php

namespace App\Jobs\Renewals;

use App\Enums\ProcessStatusCode;
use App\Enums\RenewalProcessStatuses;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsBatchEmails;
use App\Services\RenewalsUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Throwable;

class RenewalBatchEmailJob implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Stackable;

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
        /*$this->batchLeadId = $batchLeadId;
        $this->batchEmailId = $batchEmailId;
        $this->quoteTypeId = $quoteTypeId;
        $this->isCompleted = $isCompleted;*/
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
    public function handle(RenewalsUploadService $renewalsUploadFileService)
    {
        info('Renewals OCB email job started processId: '.$this->renewalQuoteProcess->id);

        $renewalsUploadFileService->renewalBatchEmailProcess($this->batch, $this->renewalsBatchEmail, $this->renewalQuoteProcess);

        info('Renewals OCB email job completed ');

        /*try {

        } catch (\Exception $e) {
            Log::info('RenewalBatchEmailJob Error: '.$e->getMessage());
            if ($this->attempts() == 3 && $this->isCompleted) {
                // Update email batch status = failed
                $batchEmail = RenewalsBatchEmails::find($this->batchEmailId);
                $batchEmail->status = ProcessStatusCode::FAILED;
                $batchEmail->save();
            }
        }*/
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
        info('CL: '.get_class().' FN: failed. Job Failed. renewalQuoteProcessId: '.$this->renewalQuoteProcess->id.' Error: '.$exception->getMessage());
        // $this->renewalQuoteProcess->update(['status' => RenewalProcessStatuses::FAILED]);
        RenewalsBatchEmails::where('id', $this->renewalsBatchEmail->id)->update(['total_failed' => DB::raw('total_failed+1')]);
    }
}
