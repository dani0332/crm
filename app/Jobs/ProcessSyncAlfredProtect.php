<?php

namespace App\Jobs;

use App\Enums\QuoteTypeId;
use App\Strategies\EmbeddedProducts\AlfredProtect;
use Exception;
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
        $strategy = new AlfredProtect;

        $quoteTypeId = QuoteTypeId::Car;
        $transactions = $this->quoteObject->embeddedTransactions()->where([
            ['quote_type_id', '=', $quoteTypeId],
            ['quote_request_id',  '=', $this->quoteObject->id],
            ['is_selected',  '=', 1],
        ])->get();

        $transaction = $transactions->where(function ($transact) {
            if (isset($transact->product) && isset($transact->product->embeddedProduct)) {
                return AlfredProtect::checkAlfredProtect($transact->product->embeddedProduct->short_code);
            } else {
                throw new Exception('No embedded product found for the selected transaction');
            }
        })->first();
        info('CL: '.get_class().' FN: handle. Transaction: '.$transaction);
        if (! isset($transaction) || empty($transaction)) {
            throw new Exception('No transaction found for the selected product');
        }

        $strategy->syncSukoonDemocrance($this->quoteObject, $transaction);
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {
        info('CL: '.get_class().' FN: failed. Job Failed. Error: '.$exception->getMessage());
    }
}
