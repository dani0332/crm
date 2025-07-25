<?php

namespace App\Jobs\EP;

use App\Repositories\EmbeddedProductRepository;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEPJob implements ShouldQueue
{
    use Dispatchable, GenericQueriesAllLobs, InteractsWithQueue, Queueable, SerializesModels;

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
        $extra = [
            'quoteId' => $this->quoteId,
            'modelType' => $this->modelType,
            'epId' => $this->epId,
        ];

        try {
            LoggerService::info('SendEPJob dispatch', extra: $extra);
            EmbeddedProductRepository::sendDocumentsByLead($this->quoteId, $this->modelType, $this->epId, callPurchaseFlow: true);
        } catch (Exception $e) {
            LoggerService::error('SendEPJob ERROR', extra: [...$extra, 'exception' => $e->getMessage()]);
        }
    }
}
