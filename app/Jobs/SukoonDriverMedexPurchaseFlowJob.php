<?php

namespace App\Jobs;

use App\Enums\QuoteTypeId;
use App\Services\Logger\LoggerService;
use App\Services\SukoonDriverMedexService;
use App\Strategies\EmbeddedProducts\EmbeddedProduct;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SukoonDriverMedexPurchaseFlowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 300;
    public $backoff = 300;
    private $quoteObject;

    /**
     * Create a new job instance.
     */
    public function __construct($lead)
    {
        $lead = $lead->load('embeddedTransactions', 'embeddedTransactions.product', 'embeddedTransactions.product.embeddedProduct', 'emirate', 'customer');
        $this->quoteObject = $lead;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $quoteTypeId = QuoteTypeId::Car;
        $transactions = $this->quoteObject->embeddedTransactions()->where([
            ['quote_type_id', '=', $quoteTypeId],
            ['quote_request_id',  '=', $this->quoteObject->id],
            ['is_selected',  '=', 1],
        ])->get();

        $transaction = $transactions->where(function ($transact) {
            if (isset($transact->product) && isset($transact->product->embeddedProduct)) {
                return EmbeddedProduct::checkSukoonDriverMedex($transact->product->embeddedProduct->short_code);
            } else {
                throw new Exception('No embedded product found for the selected transaction');
            }
        })->first();
        LoggerService::info('CL: '.get_class().' FN: handle. Transaction: '.$transaction);

        if (! isset($transaction) || empty($transaction)) {
            throw new Exception('No transaction found for the selected product');
        }
        app(SukoonDriverMedexService::class)->processPurchaseFlow($this->quoteObject, $transaction);
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {
        LoggerService::info('CL: '.get_class().' FN: failed. Job Failed. Error: '.$exception->getMessage());
    }
}
