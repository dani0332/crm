<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Services\EmailServices\HomeEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAutomatedHomeRenewalFollowup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $quoteUuid;
    public $tries = 3;
    public $timeout = 15;
    public $backoff = 60;

    public function __construct($quoteUuid)
    {
        $this->quoteUuid = $quoteUuid;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $homeAutomatedFollowupSwitch = getAppStorageValueByKey(ApplicationStorageEnums::AUTOMATED_HOME_RENEWAL_FOLLOWUP_SWITCH, useCache: true);

        $personalQuote = PersonalQuote::where('uuid', $this->quoteUuid)->where('quote_type_id', QuoteTypeId::Home)->first();

        if (! $personalQuote) {
            LoggerService::info(self::class." - Personal Quote not found for: {$this->quoteUuid}");

            return;
        }

        LoggerService::startQuoteLogging(QuoteTypes::HOME->refId($personalQuote->uuid));

        if ($homeAutomatedFollowupSwitch && $homeAutomatedFollowupSwitch == 1) {

            app(HomeEmailService::class)->sendAutomatedHomeRenewalFollowup($personalQuote);
            LoggerService::info(self::class." - Automated Home Renewal Followup sent for quote: {$personalQuote->uuid}");
        } else {
            LoggerService::info(self::class." - Automated Home Renewal Followup Switch is off for quote: {$personalQuote->uuid}");
        }
    }
}
