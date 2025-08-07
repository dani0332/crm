<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Services\EmailServices\LifeEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAutomatedLifeFollowup implements ShouldQueue
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
        $lifeAutomatedFollowupSwitch = getAppStorageValueByKey(ApplicationStorageEnums::AUTOMATED_LIFE_FOLLOWUP_SWITCH, useCache: true);

        $personalQuote = PersonalQuote::where('uuid', $this->quoteUuid)->where('quote_type_id', QuoteTypeId::Life)->first();

        LoggerService::startQuoteLogging(QuoteTypes::LIFE->refId($personalQuote->uuid));
        if ($lifeAutomatedFollowupSwitch && $lifeAutomatedFollowupSwitch == 1) {
            app(LifeEmailService::class)->sendAutomatedLifeFollowup($personalQuote);
            LoggerService::info(self::class." - Automated Life Followup sent for quote: {$personalQuote->uuid}");
        } else {
            LoggerService::info(self::class." - Automated Life Followup Switch is off for quote: {$personalQuote->uuid}");
        }
    }
}
