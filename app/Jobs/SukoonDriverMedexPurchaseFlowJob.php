<?php

namespace App\Jobs;

use App\Enums\QuoteTypeId;
use App\Services\Logger\LoggerService;
use App\Services\SukoonDriverMedexService;
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
    private $quoteTypeId;
    private $transaction;

    /**
     * Create a new job instance.
     */
    public function __construct($lead, $quoteTypeId, $transaction)
    {
        $this->quoteObject = $lead;
        $this->quoteTypeId = $quoteTypeId;
        $this->transaction = $transaction;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! in_array($this->quoteTypeId, [QuoteTypeId::Car, QuoteTypeId::Bike])) {
            throw new Exception('Only (Car / Bike) LOB are eligible');
        }

        app(SukoonDriverMedexService::class)->processPurchaseFlow($this->quoteObject, $this->quoteTypeId, $this->transaction);
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {
        LoggerService::info('CL: '.get_class().' FN: failed. Job Failed. Error: '.$exception->getMessage());
    }
}
