<?php

namespace App\Jobs;

use App\Services\Logger\LoggerService;
use App\Services\SukoonMedexService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SukoonMedexPurchaseFlowJob implements ShouldQueue
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
    public function __construct($quoteObject, $quoteTypeId, $transaction)
    {
        $this->quoteObject = $quoteObject;
        $this->quoteTypeId = $quoteTypeId;
        $this->transaction = $transaction;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sukoonMedexService = app(SukoonMedexService::class);
        $sukoonMedexService->validateCustomerDetails($this->quoteObject);
        $sukoonMedexService->initiatePurchaseFlow($this->quoteObject, $this->quoteTypeId, $this->transaction);
        $sukoonMedexService->processPurchaseFlow();
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {
        LoggerService::info('CL: '.get_class().' FN: failed. Job Failed. Error: '.$exception->getMessage());
    }
}
