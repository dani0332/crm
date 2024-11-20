<?php

namespace App\Jobs;

use App\Models\CarQuote;
use App\Models\CustomerAddress;
use App\Enums\EmbeddedProductEnum;
use App\Facades\Ken;
use App\Enums\BirdFlowStatusEnum;
use App\Enums\QuoteTypes;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;

class AddressReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 300;
    /**
     * The CarQuote instance.
     *
     * @var CarQuote
     */
    protected CarQuote $lead;

    /**
     * Create a new job instance.
     *
     * @param CarQuote $lead
     */
    public function __construct(CarQuote $lead)
    {
        $this->lead = $lead;
    }

    /**
     * Execute the job.
     *
     * @return void
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

            if (
                $courierEmbeddedTransaction->isNotEmpty()
            ) {
                info('Triggering Bird Courier Flow for policy reminder for lead : ' . $this->lead->uuid);
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
            info(self::class . ' - Error: ' . $e->getMessage() . $e->getTraceAsString());
        }
    }

    public function failed(\Throwable $exception): void
    {
        info('AddressReminderJob failed for lead: ' . $this->lead->uuid, [
            'error' => $exception->getMessage(),
        ]);
    }
}
