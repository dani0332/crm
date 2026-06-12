<?php

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\PersonalQuote;
use App\Services\EmailServices\CyberEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCyberAutomatedFollowupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = 60;
    private $quoteUuid;

    public function __construct($quoteUuid)
    {
        $this->quoteUuid = $quoteUuid;
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lead = PersonalQuote::where('uuid', $this->quoteUuid)->first();

        if (! $lead) {
            LoggerService::info(self::class.' - Cyber Lead not found');

            return;
        }
        if (in_array($lead->quote_status_id, [QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost, QuoteStatusEnum::Fake])) {
            LoggerService::info(self::class.' - Lead not eligible for Cyber Automated Followups uuid: '.$this->quoteUuid);

            return;
        }

        LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::CYBER_AUTOMATED_FOLLOWUPS);
        app(CyberEmailService::class)->sendCyberAutomatedFollowups($lead);
        LoggerService::info(self::class.' - Cyber Automated Followups sent');
        LoggerService::endLogging();
    }
}
