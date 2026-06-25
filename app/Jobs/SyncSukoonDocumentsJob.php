<?php

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
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

    public function __construct($quote, $quoteTypeId, $transaction, private bool $isSendEmail = false)
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
        LoggerService::startQuoteLogging($this->quote->code, LoggerFeatureEnum::EP_PROCESS_SYNC_DOCUMENT);

        $initialPolicyStatus = $this->transaction->policy_status ?? '';

        $sukoonMedexService = app(SukoonMedexService::class);
        $sukoonMedexService->initiatePurchaseFlow($this->quote, $this->quoteTypeId, $this->transaction);
        $sukoonMedexService->syncAndProcessSukoonDocuments();
        $sukoonMedexService->maybeSendDocumentsEmail($this->isSendEmail, $initialPolicyStatus);
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {
        LoggerService::info('SyncSukoonDocumentsJob Failed. Error: '.$exception->getMessage());
    }
}
