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
        LoggerService::startQuoteLogging($this->quoteObject);
        LoggerService::info("SukoonMedexPurchaseFlowJob - quoteTypeId: {$this->quoteTypeId} - etId: {$this->transaction->id} - isSendEmail: {$this->isSendEmail}");

        $sukoonMedexService = app(SukoonMedexService::class);
        $sukoonMedexService->initiatePurchaseFlow($this->quoteObject, $this->quoteTypeId, $this->transaction);
        $sukoonMedexService->processPurchaseFlow($this->isSendEmail);
    }

    /**
     * @return void
     */
    public function failed(Throwable $exception)
    {
        LoggerService::info('SukoonMedexPurchaseFlowJob failed', extra: [
            'quoteId' => $this->quoteObject->id ?? 'N/A',
            'quoteCode' => $this->quoteObject->code ?? 'N/A',
            'quoteTypeId' => $this->quoteTypeId,
            'etId' => $this->transaction->id ?? 'N/A',
            'etCode' => $this->transaction->code ?? 'N/A',
            'exception' => $exception->getMessage()
        ]);

        // Send failure email notification
        $message = "SukoonMedexPurchaseFlowJob - Sukoon Medex EP failure notification email";
        try {
            Mail::send(new SukoonMedexEPFailureNotification($this->quoteObject, $this->quoteTypeId));
            LoggerService::info("{$message} sent successfully");

        } catch (Throwable $emailException) {
            LoggerService::error("{$message} failed to send", extra: [
                'exception' => $emailException->getMessage()
            ]);
        }
    }
}
