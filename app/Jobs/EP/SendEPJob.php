<?php

namespace App\Jobs\EP;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Repositories\EmbeddedProductRepository;
use App\Traits\GenericQueriesAllLobs;

class SendEPJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, GenericQueriesAllLobs;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = 180;
    private $quoteId = null;
    private $modelType = null;
    private $epId = null;

    /**
     * Create a new job instance.
     */
    public function __construct($quoteId, $modelType, $epId)
    {
        $this->quoteId = $quoteId;
        $this->modelType = $modelType;
        $this->epId = $epId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {

            info("Sent EP - {$this->quoteId} - {$this->modelType} - {$this->epId} ---- ");
            EmbeddedProductRepository::sendDocumentsByLead($this->quoteId, $this->modelType, $this->epId);
            info("Sent EP completed- {$this->quoteId} - {$this->modelType} - {$this->epId} ---- ");

        } catch (Exception $e) {
            Log::error("Resent EP - {$this->quoteId} - {$this->modelType} - {$this->epId} - ERROR - " . $e->getMessage());
        }
    }
}
