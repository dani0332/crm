<?php

namespace App\Jobs;

use App\Enums\ProcessStatusCode;
use App\Models\RenewalsBatchEmails;
use App\Services\RenewalsUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RenewalBatchEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batchLeadId;
    protected $batchEmailId;
    protected $quoteTypeId;
    protected $isCompleted;
    protected $batch;
    public $tries = 3;
    public $timeout = 30;
    public $backoff = 35;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($batchLeadId, $batchEmailId, $quoteTypeId, $isCompleted, $batch)
    {
        $this->batchLeadId = $batchLeadId;
        $this->batchEmailId = $batchEmailId;
        $this->quoteTypeId = $quoteTypeId;
        $this->isCompleted = $isCompleted;
        $this->batch = $batch;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RenewalsUploadService $renewalsUploadFileService)
    {
        $this->renewalsUploadFileService = $renewalsUploadFileService;
        try {
            $this->renewalsUploadFileService->renewalBatchEmailProcess($this->batchLeadId, $this->batchEmailId, $this->quoteTypeId, $this->isCompleted, $this->batch);
        } catch (\Exception $e) {
            Log::info('RenewalBatchEmailJob message: '.$e->getMessage());
            if ($this->attempts() == 3 && $this->isCompleted) {
                // Update email batch status = failed
                $batchEmail = RenewalsBatchEmails::find($this->batchEmailId);
                $batchEmail->status = ProcessStatusCode::FAILED;
                $batchEmail->save();
            }
        }
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->batchLeadId))->dontRelease()];
    }
}
