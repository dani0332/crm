<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\RenewalsUploadService;
use Illuminate\Support\Facades\Log;
use DB;

class RenewalBatchEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $batchLeadId;
    protected $renewalsUploadFileService;
    protected $batchEmailId;

    public $tries = 5;
    public $timeout = 300;
    public $backoff = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($batchLeadId, RenewalsUploadService $renewalsUploadFileService, $batchEmailId)
    {
        $this->batchLeadId = $batchLeadId;
        $this->renewalsUploadFileService = $renewalsUploadFileService;
        $this->batchEmailId = $batchEmailId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $this->renewalsUploadFileService->renewalBatchEmailProcess($this->batchLeadId, $this->batchEmailId);
        } catch (\Exception $e) {
            Log::info("message: " . $e->getMessage());
            if ($this->attempts() < 4) {
                $delayInSeconds = 5 * 60;
                $this->release($delayInSeconds);
            }
        } finally {
            DB::disconnect('mysql');
        }
    }
}
