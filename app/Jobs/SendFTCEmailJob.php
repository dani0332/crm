<?php

namespace App\Jobs;

use App\Enums\QuoteTypes;
use App\Facades\Marshall;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFTCEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $tries = 3;
    private $timeout = 60;
    private $backoff = 300;
    private $quoteUUID;
    private $quoteType;
    private $paymentLink;
    private $isInsurerPayment;

    /**
     * Create a new job instance.
     */
    public function __construct(string $quoteUUID, QuoteTypes $quoteType, bool $isInsurerPayment = false)
    {
        $this->quoteUUID = $quoteUUID;
        $this->quoteType = $quoteType;
        $this->isInsurerPayment = $isInsurerPayment;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Define eligible SIC types
        $nonEligibleSICTypes = [QuoteTypes::BIKE->id(), QuoteTypes::HOME->id(), QuoteTypes::HEALTH->id()];

        try {
            LoggerService::info('Trying to Send FTC Email if lead is SIC and Payment is Authorized and Advisor is Assigned for uuid', '', ['feature' => 'SendFTCEmailJob', 'ref_id' => $this->quoteUUID]);
            $isSic = false;
            $leadQuery = $this->quoteType->model()::with('payments')
                ->whereNotNull('advisor_id')
                ->where('uuid', $this->quoteUUID);

            // only add isSIC check if the quote type is not in the nonEligibleSICTypes
            if (! in_array($this->quoteType->id(), $nonEligibleSICTypes, true)) {
                $leadQuery->isSICLead($this->quoteType);
                $isSic = true;
            }

            // Fetch the lead
            $lead = $leadQuery->first();

            // Lead must be SIC LEAD and payment authorized
            if ($lead) {
                $isPaymentAuthorized = $lead->isPaymentAuthorized();
                if ($isPaymentAuthorized || $lead->isPaymentLinkRequested() || $this->isInsurerPayment) {
                    $data = [
                        'quoteUID' => $this->quoteUUID,
                        'quoteTypeId' => (int) $this->quoteType->id(),
                        'isSic' => $isSic,
                        'isInsurerPaymentLink' => $this->isInsurerPayment,
                    ];

                    Marshall::request('/payment/send-payment-auth-email', 'post', $data);
                    LoggerService::info('Email Sent Successfully for uuid', '', ['feature' => 'SendFTCEmailJob', 'ref_id' => $this->quoteUUID]);
                } else {
                    LoggerService::info('Payment not authorized for uuid', '', ['feature' => 'SendFTCEmailJob', 'ref_id' => $this->quoteUUID]);
                }
            } else {
                LoggerService::info('Quote not found for uuid', '', ['feature' => 'SendFTCEmailJob', 'ref_id' => $this->quoteUUID]);
            }
        } catch (Exception $e) {
            LoggerService::error('Error sending FTC email', [
                'quoteUUID' => $this->quoteUUID,
                'quoteType' => $this->quoteType->id(),
                'isInsurerPayment' => $this->isInsurerPayment,
            ], $e, ['ref_id' => $this->quoteUUID, 'feature' => 'SendFTCEmailJob']);
        }
    }
}
