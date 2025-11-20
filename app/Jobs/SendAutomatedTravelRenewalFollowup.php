<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteTypes;
use App\Models\TravelQuote;
use App\Services\BirdService;
use App\Services\EmailServices\TravelEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAutomatedTravelRenewalFollowup implements ShouldQueue
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
        $travelAutomatedFollowupSwitch = getAppStorageValueByKey(ApplicationStorageEnums::AUTOMATED_TRAVEL_RENEWAL_FOLLOWUP_SWITCH, useCache: true);

        $travelQuote = TravelQuote::where('uuid', $this->quoteUuid)->first();

        if (! $travelQuote) {
            LoggerService::info(self::class." - Travel Quote not found for: {$this->quoteUuid}");

            return;
        }

        LoggerService::startQuoteLogging(QuoteTypes::TRAVEL->refId($travelQuote->uuid));

        // Check if automated follow-up is already executed to prevent duplicates
        $isFollowupExecuted = app(BirdService::class)
            ->isFollowupExecuted($travelQuote->uuid, QuoteTypes::TRAVEL->id(), QuoteFlowType::TRAVEL_RENEWAL_AUTOMATED_FOLLOWUPS->value);

        if ($isFollowupExecuted) {
            LoggerService::info(self::class." - TRAVEL_RENEWAL_AUTOMATED_FOLLOWUPS - Followup already executed {$travelQuote->uuid}");

            return;
        }

        if ($travelAutomatedFollowupSwitch && $travelAutomatedFollowupSwitch == 1) {

            app(TravelEmailService::class)->sendAutomatedTravelRenewalFollowup($travelQuote);
            LoggerService::info(self::class." - Automated Travel Renewal Followup sent for quote: {$travelQuote->uuid}");
        } else {
            LoggerService::info(self::class." - Automated Travel Renewal Followup Switch is off for quote: {$travelQuote->uuid}");
        }
    }
}

