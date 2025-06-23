<?php

namespace App\Jobs;

use App\Services\SukoonMedexService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncSukoonDocuments implements ShouldQueue
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
        $sukoonService = new SukoonMedexService;
        $sukoonService->initiatePurchaseFlow($this->quote, $this->quoteTypeId, $this->transaction);
        $sukoonService->syncSukoonDocuments();
    }
}
