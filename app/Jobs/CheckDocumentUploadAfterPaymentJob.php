<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTypes;
use App\Models\Payment;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckDocumentUploadAfterPaymentJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $uniqueFor = 90000; // 25 hours (24 hours delay + 1 hour buffer)
    public $tries = 1;
    private string $paymentCode;

    /**
     * Create a new job instance.
     */
    public function __construct(string $paymentCode)
    {
        $this->paymentCode = $paymentCode;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::CHECK_DOCUMENT_UPLOAD_AFTER_PAYMENT);

        LoggerService::info("CheckDocumentUploadAfterPaymentJob: Starting job execution for payment code: {$this->paymentCode}");

        $payment = Payment::whereCode($this->paymentCode)
            ->with('paymentable.documents')
            ->first();
        if (! $payment) {
            LoggerService::info("CheckDocumentUploadAfterPaymentJob: Payment not found or not authorised for payment code: {$this->paymentCode}");

            return;
        }

        $quote = $payment->paymentable;
        if (! $quote) {
            LoggerService::info("CheckDocumentUploadAfterPaymentJob: Quote not found for payment code: {$this->paymentCode}");

            return;
        }

        $documents = $quote->documents;
        if ($documents->count() == 0) {
            LoggerService::info("CheckDocumentUploadAfterPaymentJob: No documents found for payment code: {$this->paymentCode}");

            QuoteTypes::CYBER->allocate(uuid: $quote->uuid);

            LoggerService::info("CheckDocumentUploadAfterPaymentJob: Allocated advisor to quote: {$quote->uuid} for payment code: {$this->paymentCode}");

            return;
        } else {
            LoggerService::info("CheckDocumentUploadAfterPaymentJob: Documents found for payment code: {$this->paymentCode}");

            return;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        LoggerService::error("CheckDocumentUploadAfterPaymentJob: Job failed for payment code: {$this->paymentCode}", exception: $exception);
    }

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return 'check-document-upload-payment-'.$this->paymentCode;
    }
}
