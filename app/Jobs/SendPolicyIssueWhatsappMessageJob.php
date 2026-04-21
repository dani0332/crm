<?php

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTypes;
use App\Services\CentralService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Carbon;

class SendPolicyIssueWhatsappMessageJob implements ShouldQueue
{
    use GenericQueriesAllLobs;
    use Queueable;

    public $tries = 3;
    public $timeout = 100;
    public $backoff = 300;
    private $quoteUuid;
    private $quote;
    private $quoteTypeId;
    private $lockPostfix;

    /**
     * Create a new job instance.
     */
    public function __construct($quoteUuid, $quoteTypeId)
    {
        $this->quoteUuid = $quoteUuid;
        $this->quoteTypeId = $quoteTypeId;
        $this->lockPostfix = $quoteUuid.'-'.Carbon::now()->format('YmdHi'); // lock postfix to release the WithoutOverlapping lock i.e 2024102113
        LoggerService::info(self::class.' fn:'.__FUNCTION__.' - Quote Code '.$quoteUuid.' sendPolicyIssueWhatsappMessageJob dispatched ', extra: [
            'uuid' => $quoteUuid,
            'quoteTypeId' => $quoteTypeId,
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $quoteType = QuoteTypes::getName($this->quoteTypeId)->value ?? '';
            $this->quote = $this->getQuoteObjectBy($quoteType, $this->quoteUuid, 'uuid');
            LoggerService::startQuoteLogging($this->quote, LoggerFeatureEnum::POLICY_ISSUE_WHATSAPP_MESSAGE);

            if (! $this->quote) {
                LoggerService::info(self::class." - Lead not found for Quote Code : {$this->quote?->code}", extra: [
                    'uuid' => $this->quoteUuid,
                    'quoteTypeId' => $this->quoteTypeId,
                ]);

                return false;
            }
            if (! $this->quote->mobile_no) {
                LoggerService::info(self::class." - Mobile number not found for Quote Code : {$this->quote?->code}", extra: [
                    'uuid' => $this->quoteUuid,
                    'quoteTypeId' => $this->quoteTypeId,
                ]);

                return false;
            }

            LoggerService::info('Going to send Policy Issued Whatsapp Message');
            $responseCode = (new CentralService)->sendPolicyIssuedWhatsappMessage($this->quote, $this->quoteTypeId);
            LoggerService::info('Policy Issued Whatsapp Message Sent: '.$responseCode);

            if (in_array($responseCode, [200, 201])) {
                LoggerService::info(self::class." - Policy Issued Whatsapp Message Sent: {$responseCode} Customer Phone: {$this->quote?->mobile_no} Quote Code: {$this->quote?->code} , QuoteTypeId {$this->quoteTypeId}", extra: [
                    'uuid' => $this->quoteUuid,
                    'quoteTypeId' => $this->quoteTypeId,
                ]);
            } else {
                LoggerService::error(self::class." - Policy Issued Whatsapp Message Not Sent: {$responseCode} Customer Phone: {$this->quote?->mobile_no} Quote Code: {$this->quote?->code} , QuoteTypeId {$this->quoteTypeId}", extra: [
                    'uuid' => $this->quoteUuid,
                    'quoteTypeId' => $this->quoteTypeId,
                ]);
            }

        } catch (Exception $e) {
            LoggerService::error(self::class." - Error: {$e->getMessage()} for Quote Code {$this->quote?->code} with stack trace {$e->getTraceAsString()}", extra: [
                'uuid' => $this->quoteUuid,
                'quoteTypeId' => $this->quoteTypeId,
            ]);
        }

    }

    public function middleware()
    {
        // release the WithoutOverlapping lock 5 minutes after the job has processed
        return [(new WithoutOverlapping($this->lockPostfix))->dontRelease()];
    }
}
