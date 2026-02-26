<?php

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Services\Logger\LoggerService;
use App\Services\SukoonMedexService;
use App\Traits\SendsEpFailureEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SukoonMedexPurchaseFlowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SendsEpFailureEmail, SerializesModels;

    public $tries = 3;
    public $timeout = 300;
    public $backoff = 180;
    private $quoteObject;
    private $quoteTypeId;
    private $transaction;
    private $isSendEmail = false;
    private string $logPrefix = 'SukoonMedex - PurchaseFlowJob:';
    private array $logExtra = [];

    /**
     * Create a new job instance.
     */
    public function __construct($quoteObject, $quoteTypeId, $transaction, $isSendEmail = false)
    {
        $this->quoteObject = $quoteObject;
        $this->quoteTypeId = $quoteTypeId;
        $this->transaction = $transaction;
        $this->isSendEmail = $isSendEmail;

        $this->logExtra = [
            'quoteTypeId' => $this->quoteTypeId,
            'etId' => $this->transaction?->id ?? '-',
            'isSendEmail' => $this->isSendEmail,
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        LoggerService::startQuoteLogging($this->quoteObject->code, LoggerFeatureEnum::EP_PROCESS_PURCHASE_FLOW);
        try {

            LoggerService::info($this->logPrefix, extra: $this->logExtra);

            $sukoonMedexService = app(SukoonMedexService::class);
            $sukoonMedexService->initiatePurchaseFlow($this->quoteObject, $this->quoteTypeId, $this->transaction);
            $sukoonMedexService->processPurchaseFlow($this->isSendEmail);
        } catch (Throwable $e) {

            LoggerService::info("{$this->logPrefix} Failed", extra: [...$this->logExtra, 'exception' => $e->getMessage()]);
            $this->sendFailureEmail();
        }
    }

    private function sendFailureEmail()
    {
        $transactionId = $this->transaction?->id ?? null;
        $this->sendSukoonMedexFailureEmail($this->quoteObject, $this->quoteTypeId, $transactionId, $this->logPrefix);
    }
}
