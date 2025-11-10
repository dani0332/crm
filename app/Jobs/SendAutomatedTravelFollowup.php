<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypes;
use App\Models\TravelQuote;
use App\Services\EmailServices\TravelEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAutomatedTravelFollowup implements ShouldQueue
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
        $travelAutomatedFollowupSwitch = getAppStorageValueByKey(ApplicationStorageEnums::AUTOMATED_TRAVEL_FOLLOWUP_SWITCH, useCache: true);

        $travelQuote = TravelQuote::where('uuid', $this->quoteUuid)->first();

        if (! $travelQuote) {
            LoggerService::info(self::class." - Travel Quote not found for: {$this->quoteUuid}");

            return;
        }

        LoggerService::startQuoteLogging(QuoteTypes::TRAVEL->refId($travelQuote->uuid));

        if ($travelAutomatedFollowupSwitch && $travelAutomatedFollowupSwitch == 1) {
            app(TravelEmailService::class)->sendAutomatedTravelFollowup($travelQuote);
            LoggerService::info(self::class." - Automated Travel Followup sent for quote: {$travelQuote->uuid}");
        } else {
            LoggerService::info(self::class." - Automated Travel Followup Switch is off for quote: {$travelQuote->uuid}");
        }
    }
}
