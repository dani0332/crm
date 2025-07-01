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

    public $tries = 3;
    public $timeout = 300;
    public $backoff = 180;
    private $quoteObject;
    private $quoteTypeId;
    private $transaction;
    private $isSendEmail = false;

    /**
     * Create a new job instance.
     */
    public function __construct($quoteObject, $quoteTypeId, $transaction, $isSendEmail = false)
    {
        $this->quoteObject = $quoteObject;
        $this->quoteTypeId = $quoteTypeId;
        $this->transaction = $transaction;
        $this->isSendEmail = $isSendEmail;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sukoonMedexService = app(SukoonMedexService::class);
        $sukoonMedexService->initiatePurchaseFlow($this->quoteObject, $this->quoteTypeId, $this->transaction);
        $sukoonMedexService->processPurchaseFlow($this->isSendEmail);
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {
        LoggerService::info('Sukoon Medex Purchase-Flow Job failed. Job Failed. Error: '.$exception->getMessage());
    }
}
