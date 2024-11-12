<?php

namespace App\Jobs\EP;

use App\Enums\QuoteTypeId;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Repositories\EmbeddedProductRepository;
use App\Traits\GenericQueriesAllLobs;

class ResendEPJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, GenericQueriesAllLobs;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = 180;
    private $uuid = null;
    private $quoteTypeId = null;

    /**
     * Create a new job instance.
     */
    public function __construct($uuid, $quoteTypeId)
    {
        $this->uuid = $uuid;
        $this->quoteTypeId = $quoteTypeId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {

            $quoteType = QuoteTypeId::getOptions()[$this->quoteTypeId];
            $quote = $this->getQuoteObject($quoteType, $this->uuid);
            info("Resent EP - {$this->uuid} - {$this->quoteTypeId} ---- ");
            EmbeddedProductRepository::sendDocumentsByLead($quote->id, $quoteType, null, true);
            info("Resent EP completed- {$this->uuid} - {$this->quoteTypeId} ---- ");

        } catch (Exception $e) {
            Log::error("Resent EP - {$this->uuid} - {$this->quoteTypeId} - ERROR - " . $e->getMessage());
        }
    }
}
