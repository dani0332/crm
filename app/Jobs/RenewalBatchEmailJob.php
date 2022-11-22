<?php

namespace App\Jobs;

use App\Services\RenewalsUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RenewalBatchEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batchLeadId;
    protected $batchEmailId;
    protected $quoteTypeId;
    protected $isCompleted;
    public $tries = 3;
    public $timeout = 30;
    public $backoff = 35;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($batchLeadId, $batchEmailId, $quoteTypeId, $isCompleted)
    {
        $this->batchLeadId = $batchLeadId;
        $this->batchEmailId = $batchEmailId;
        $this->quoteTypeId = $quoteTypeId;
        $this->isCompleted = $isCompleted;
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
            $this->renewalsUploadFileService->renewalBatchEmailProcess($this->batchLeadId, $this->batchEmailId, $this->quoteTypeId, $this->isCompleted);
        } catch (\Exception $e) {
            Log::info('RenewalBatchEmailJob message: '.$e->getMessage());
            if ($this->attempts() < 4) {
                $delayInSeconds = 5 * 60;
                $this->release($delayInSeconds);
            }
        }
    }
}
