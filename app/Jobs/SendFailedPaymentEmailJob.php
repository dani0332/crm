<?php

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTypes;
use App\Facades\Marshall;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFailedPaymentEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $tries = 3;
    private $timeout = 60;
    private $backoff = 300;

    public function __construct(protected string $quoteUUID, protected QuoteTypes $quoteType)
    {
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        LoggerService::startQuoteLogging($this->quoteType->refId($this->quoteUUID), LoggerFeatureEnum::SEND_FAILED_PAYMENT_EMAIL);

        try {
            LoggerService::info('Trying to Send Failed Payment Email');
            $lead = $this->quoteType->model()->with('payments')
                ->whereNotNull('advisor_id')
                ->where('uuid', $this->quoteUUID)
                ->first();

            if ($lead) {
                $isPaymentDeclinedOrFailed = $lead->isPaymentDeclinedOrFailed();
                if ($isPaymentDeclinedOrFailed) {
                    $data = [
                        'quoteUID' => $this->quoteUUID,
                        'quoteTypeId' => (int) $this->quoteType->id(),
                    ];

                    Marshall::request('/payment/send-failed-declined-reason-email', 'post', $data);
                    LoggerService::info('Email Sent Successfully');
                } else {
                    LoggerService::info('Payment not declined or failed');
                }
            } else {
                LoggerService::info('Quote not found');
            }
        } catch (Exception $e) {
            LoggerService::error('Error sending Failed Payment Email', exception: $e);
        }
    }
}
