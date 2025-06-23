<?php

namespace App\Jobs;

use App\Services\SukoonMedexService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        $sukoonMedexService->syncSukoonDocuments();
    }
}
