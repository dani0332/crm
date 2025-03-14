<?php

namespace App\Jobs;

use App\Services\CentralService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendPolicyIssueWhatsappMessageJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 100;
    /*public $backoff = 300;*/
    private $quote;
    private $quoteTypeId;

    /**
     * Create a new job instance.
     */
    public function __construct($quote, $quoteTypeId)
    {
        $this->quote = $quote;
        $this->quoteTypeId = $quoteTypeId;
        info(self::class.' fn:'.__FUNCTION__.' - Quote Code '.$quote->code.' sendPolicyIssueWhatsappMessageJob dispatched ');
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $this->quote = $this->quote->refresh();

            if (! $this->quote) {
                info(self::class." - Lead not found for Quote Code : {$this->quote->code}");

                return false;
            }
            if (! $this->quote->mobile_no) {
                info(self::class." - Mobile number not found for Quote Code : {$this->quote->code}");

                return false;
            }

            $responseCode = (new CentralService)->sendPolicyIssuedWhatsappMessage($this->quote, $this->quoteTypeId);

            if (in_array($responseCode, [200, 201])) {
                info(self::class." - Policy Issued Whatsapp Message Sent: {$responseCode} Customer Phone: {$this->quote->mobile_no} Quote Code: {$this->quote->code} , QuoteTypeId {$this->quoteTypeId}");
            } else {
                Log::error(self::class." - Policy Issued Whatsapp Message Not Sent: {$responseCode} Customer Phone: {$this->quote->mobile_no} Quote Code: {$this->quote->code} , QuoteTypeId {$this->quoteTypeId}");
            }

        } catch (Exception $e) {
            Log::error(self::class." - Error: {$e->getMessage()} for Quote Code {$this->quote->code} with stack trace {$e->getTraceAsString()}");
        }

    }
}
