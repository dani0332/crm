<?php

namespace App\Jobs;

use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\EmbeddedTransaction;
use App\Strategies\EmbeddedProducts\AlfredProtect;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessSyncAlfredProtect implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 1200;
    public $backoff = 10;
    private $embeddedProduct;
    private $quoteObject;
    private $modelType;

    /**
     * Create a new job instance.
     */
    public function __construct($embeddedProduct, $quoteObject, $modelType)
    {
        $this->embeddedProduct = $embeddedProduct;
        $this->quoteObject = $quoteObject;
        $this->modelType = $modelType;
        $this->onQueue('renewals');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $strategy = new AlfredProtect();
        $optionsIds = [];
        if ($this->embeddedProduct->prices) {
            $optionsIds = $this->embeddedProduct->prices->pluck('id');
        }

        // certificate generation
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($this->modelType));
        $transaction = EmbeddedTransaction::where([
            ['quote_type_id', '=', $quoteTypeId],
            ['quote_request_id',  '=', $this->quoteObject->id],
            ['is_selected',  '=', true],
            ['payment_status_id',  '=', PaymentStatusEnum::CAPTURED],
        ])->whereIn('product_id', $optionsIds)->get();
        
        $strategy->syncSukoonDemocrance($this->quoteObject, $this->embeddedProduct, $transaction[0]);
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {
        info('CL: ' . get_class() . ' FN: failed. Job Failed. Error: ' . $exception->getMessage());
    }
}
