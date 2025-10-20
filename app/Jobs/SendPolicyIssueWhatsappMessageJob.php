<?php

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Services\CentralService;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Carbon;

class SendPolicyIssueWhatsappMessageJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 100;
    public $backoff = 300;
    private $quote;
    private $quoteTypeId;

    private $lockPostfix;

    /**
     * Create a new job instance.
     */
    public function __construct($quote, $quoteTypeId)
    {
        $this->quote = $quote;
        $this->quoteTypeId = $quoteTypeId;
        $this->lockPostfix = Carbon::now()->format('YmdHi'); // lock postfix to release the WithoutOverlapping lock i.e 2024102113
        LoggerService::info(self::class.' fn:'.__FUNCTION__.' - Quote Code '.$quote?->code.' sendPolicyIssueWhatsappMessageJob dispatched ');
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $this->quote = $this->quote->refresh();
            LoggerService::startQuoteLogging($this->quote, LoggerFeatureEnum::POLICY_ISSUE_WHATSAPP_MESSAGE);

            if (! $this->quote) {
                LoggerService::info(self::class." - Lead not found for Quote Code : {$this->quote?->code}");

                return false;
            }
            if (! $this->quote->mobile_no) {
                LoggerService::info(self::class." - Mobile number not found for Quote Code : {$this->quote?->code}");

                return false;
            }

            $responseCode = (new CentralService)->sendPolicyIssuedWhatsappMessage($this->quote, $this->quoteTypeId);

            if (in_array($responseCode, [200, 201])) {
                LoggerService::info(self::class." - Policy Issued Whatsapp Message Sent: {$responseCode} Customer Phone: {$this->quote?->mobile_no} Quote Code: {$this->quote?->code} , QuoteTypeId {$this->quoteTypeId}");
            } else {
                LoggerService::error(self::class." - Policy Issued Whatsapp Message Not Sent: {$responseCode} Customer Phone: {$this->quote?->mobile_no} Quote Code: {$this->quote?->code} , QuoteTypeId {$this->quoteTypeId}");
            }

        } catch (Exception $e) {
            LoggerService::error(self::class." - Error: {$e->getMessage()} for Quote Code {$this->quote?->code} with stack trace {$e->getTraceAsString()}");
        }

    }

    public function middleware()
    {
        // release the WithoutOverlapping lock 5 minutes after the job has processed
        return [(new WithoutOverlapping($this->quote->code.'-'.$this->lockPostfix))->dontRelease()];
    }
}
