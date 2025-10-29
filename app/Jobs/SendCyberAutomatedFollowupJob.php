<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Services\EmailServices\CyberEmailService;

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
        LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::CYBER_AUTOMATED_FOLLOWUPS);
        if (! $lead) {
            LoggerService::info(self::class." - Cyber Lead not found");

            return;
        }
        app(CyberEmailService::class)->sendCyberAutomatedFollowups($lead);
        LoggerService::info(self::class." - Cyber Automated Followups sent");


        
    }
}
