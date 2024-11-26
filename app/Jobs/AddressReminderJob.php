<?php

namespace App\Jobs;

use App\Enums\BirdFlowStatusEnum;
use App\Enums\EmbeddedProductEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypes;
use App\Facades\Ken;
use App\Models\CarQuote;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AddressReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 300;

    /**
     * The CarQuote instance.
     */
    private CarQuote $lead;

    /**
     * Create a new job instance.
     */
    public function __construct(CarQuote $lead)
    {
        $this->lead = $lead;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // send address reminder to customer if address is not entered
            if ($this->lead->embeddedTransactions()->exists()) {
                $courierEmbeddedTransaction = $this->lead->embeddedTransactions
                    ->filter(function ($transaction) {
                        return $transaction->product?->embeddedProduct?->short_code === EmbeddedProductEnum::COURIER;
                    });
            }

            if ($courierEmbeddedTransaction?->firstWhere('is_selected', 1)?->payment_status_id === PaymentStatusEnum::CAPTURED) {
                info('Triggering Bird Courier Flow for policy reminder for lead : '.$this->lead->uuid);
                $embeddedTransactionRefId = $courierEmbeddedTransaction->first()->code;
                $payload = [
                    'quoteUID' => $this->lead->uuid,
                    'quoteTypeId' => (int) QuoteTypes::CAR->id(),
                    'actionType' => BirdFlowStatusEnum::POLICY_ISSUED,
                    'refId' => $embeddedTransactionRefId,
                ];

                Ken::request('/trigger-bird-courier-flow', 'post', $payload);
            }
        } catch (Exception $e) {
            info(self::class.' - Error: '.$e->getMessage().$e->getTraceAsString());
        }
    }

    public function failed(\Throwable $exception): void
    {
        info('AddressReminderJob failed for lead: '.$this->lead->uuid, [
            'error' => $exception->getMessage(),
        ]);
    }
}
