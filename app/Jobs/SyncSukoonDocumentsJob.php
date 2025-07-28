<?php

namespace App\Jobs;

use App\Services\Logger\LoggerService;
use App\Services\SukoonMedexService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SyncSukoonDocumentsJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $quote;
    protected $quoteTypeId;
    protected $transaction;

    /**
     * Create a new job instance.
     */
    public function __construct($quote, $quoteTypeId, $transaction)
    {
        $this->quote = $quote;
        $this->quoteTypeId = $quoteTypeId;
        $this->transaction = $transaction;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sukoonMedexService = app(SukoonMedexService::class);
        $sukoonMedexService->initiatePurchaseFlow($this->quote, $this->quoteTypeId, $this->transaction);
        $sukoonMedexService->syncAndProcessSukoonDocuments();
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {
        LoggerService::info('SyncSukoonDocumentsJob Failed. Error: '.$exception->getMessage());
    }
}
