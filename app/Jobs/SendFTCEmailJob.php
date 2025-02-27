<?php

namespace App\Jobs;

use App\Enums\QuoteTypes;
use App\Facades\Marshall;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendFTCEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 300;
    public $quoteUUID;
    public $quoteType;

    /**
     * Create a new job instance.
     */
    public function __construct(string $quoteUUID, QuoteTypes $quoteType)
    {
        $this->quoteUUID = $quoteUUID;
        $this->quoteType = $quoteType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Define eligible SIC types
        $nonEligibleSICTypes = [QuoteTypes::BIKE->id(), QuoteTypes::HOME->id()];

        try {
            info(self::class." - Trying to Send FTC Email if lead is SIC and Payment is Authorized and Advisor is Assigned for uuid {$this->quoteUUID}");

            $leadQuery = $this->quoteType->model()::with('payments')
                ->whereNotNull('advisor_id')
                ->where('uuid', $this->quoteUUID);

            // only add isSIC check if the quote type is not in the nonEligibleSICTypes
            if (! in_array($this->quoteType->id(), $nonEligibleSICTypes, true)) {
                $leadQuery->isSICLead($this->quoteType);
            }

            // Fetch the lead
            $lead = $leadQuery->first();

            // Lead must be SIC LEAD and payment authorized
            if ($lead) {
                $isPaymentAuthorized = $lead->isPaymentAuthorized();
                if ($isPaymentAuthorized) {
                    $data = [
                        'quoteUID' => $this->quoteUUID,
                        'quoteTypeId' => (int) $this->quoteType->id(),
                        'isSic' => true,
                    ];

                    Marshall::request('/payment/send-payment-auth-email', 'post', $data);
                    info(self::class." - Email Sent Sucessfully for uuid: {$this->quoteUUID}");
                } else {
                    info(self::class." - Payment not authorized for uuid {$this->quoteUUID}");
                }
            } else {
                info(self::class." - Quote not found for uuid {$this->quoteUUID}");
            }
        } catch (Exception $e) {
            Log::error(self::class.' - Error: '.$e->getMessage().$e->getTraceAsString());
        }
    }
}
