<?php

namespace App\Jobs;

use App\Mail\SukoonMedexEPFailureNotification;
use App\Services\Logger\LoggerService;
use App\Services\SukoonMedexService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
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
        LoggerService::startQuoteLogging($this->quoteObject);
        LoggerService::info($this->logPrefix, extra: $this->logExtra);

        $sukoonMedexService = app(SukoonMedexService::class);
        $sukoonMedexService->initiatePurchaseFlow($this->quoteObject, $this->quoteTypeId, $this->transaction);
        $sukoonMedexService->processPurchaseFlow($this->isSendEmail);
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {
        LoggerService::info("{$this->logPrefix} Failed", extra: [...$this->logExtra, 'exception' => $exception->getMessage()]);

        // Send failure email notification
        try {
            Mail::send(new SukoonMedexEPFailureNotification($this->quoteObject, $this->quoteTypeId));
            LoggerService::info("{$this->logPrefix} - Send EP failure notification email successfully");

        } catch (Throwable $emailException) {
            LoggerService::error("{$this->logPrefix} - Send EP failure notification email Failed", extra: [
                'exception' => $emailException->getMessage(),
            ]);
        }
    }
}
