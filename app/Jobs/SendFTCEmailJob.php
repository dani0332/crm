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
    private $isPaymentBypass;

    /**
     * Create a new job instance.
     */
    public function __construct(string $quoteUUID, QuoteTypes $quoteType, bool $isInsurerPayment = false, bool $isPaymentBypass = false)
    {
        $this->quoteUUID = $quoteUUID;
        $this->quoteType = $quoteType;
        $this->isInsurerPayment = $isInsurerPayment;
        $this->isPaymentBypass = $isPaymentBypass;

        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        LoggerService::startQuoteLogging($this->quoteType->refId($this->quoteUUID), LoggerFeatureEnum::FTC_EMAIL);

        // Define eligible SIC types
        $nonEligibleSICTypes = [QuoteTypes::TRAVEL->id(), QuoteTypes::BIKE->id(), QuoteTypes::HOME->id(), QuoteTypes::HEALTH->id(), QuoteTypes::PET->id(), QuoteTypes::YACHT->id(), QuoteTypes::LIFE->id(), QuoteTypes::YACHT->id(), QuoteTypes::BUSINESS->id(), QuoteTypes::CYCLE->id(), QuoteTypes::DEVICE->id()];

        try {
            LoggerService::info('Trying to Send FTC Email if lead is SIC and Payment is Authorized and Advisor is Assigned');
            $isSic = false;
            $leadQuery = $this->quoteType->model()::with('payments')
                ->whereNotNull('advisor_id')
                ->where('uuid', $this->quoteUUID);

            // only add isSIC check if the quote type is not in the nonEligibleSICTypes
            if (! in_array($this->quoteType->id(), $nonEligibleSICTypes, true)) {
                $leadQuery->isSIC($this->quoteType);
                $isSic = true;
            }
            LoggerService::sql('FTC lead fetch criteria', $leadQuery);
            // Fetch the lead
            $lead = $leadQuery->first();

            // Lead must be SIC LEAD and payment authorized
            if ($lead) {
                $isPaymentAuthorized = $lead->isPaymentAuthorized();
                if ($isPaymentAuthorized || $lead->isPaymentLinkRequested() || $this->isInsurerPayment || $this->isPaymentBypass) {
                    $data = [
                        'quoteUID' => $this->quoteUUID,
                        'quoteTypeId' => (int) $this->quoteType->id(),
                        'isSic' => $isSic,
                        'isInsurerPaymentLink' => $this->isInsurerPayment,
                    ];

                    Marshall::request('/payment/send-payment-auth-email', 'post', $data);
                    LoggerService::info('Email Sent Successfully');
                } else {
                    LoggerService::info('Payment not authorized');
                }
            } else {
                LoggerService::info('Quote not found');
            }
        } catch (Exception $e) {
            LoggerService::error('Error sending FTC email', [
                'quoteUUID' => $this->quoteUUID,
                'quoteType' => $this->quoteType->id(),
                'isInsurerPayment' => $this->isInsurerPayment,
            ], $e);
        }
    }
}
