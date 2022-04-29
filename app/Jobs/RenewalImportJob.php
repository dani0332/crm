<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\RenewalsUploadService;
use Illuminate\Support\Facades\Log;

class RenewalImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $quoteData;
    protected $quoteType;
    protected $renewalsUploadService;
    protected $fileName;
    protected $renewalImportCode;

    public $maxTries = 5;
    public $timeout = 300;
    public $backoff = 3;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($quoteData, $quoteType, RenewalsUploadService $renewalsUploadService, $fileName, $renewalImportCode)
    {
        $this->quoteType = $quoteType;
        $this->quoteData = $quoteData;
        $this->renewalsUploadService = $renewalsUploadService;
        $this->fileName = $fileName;
        $this->renewalImportCode = $renewalImportCode;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $quoteData = $this->quoteData;
            $quoteType = $this->quoteType;

            // Sending request with data to create renewal and normal quote
            $this->renewalsUploadService->createNewQuote($quoteData, $quoteType, $this->fileName, $this->renewalImportCode);
        }
        catch(\Exception $e) {
            Log::channel('daily')->info("message: ".$e->getMessage());
            if ($this->attempts() < 4) {
                $delayInSeconds = 5 * 60;
                $this->release($delayInSeconds);
            }
        }
    }
}
