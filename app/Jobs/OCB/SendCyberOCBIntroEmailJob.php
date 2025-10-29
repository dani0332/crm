<?php

namespace App\Jobs\OCB;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTypes;

class SendCyberOCBIntroEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 3;
    public $timeout = 40;
    public $backoff = 300;
 

    /**
     * Create a new job instance.
     */
    public function __construct(private QuoteTypes $quoteType, private string $quoteUuid)
    { 
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        LoggerService::startQuoteLogging($this->quoteType->refId($this->quoteUuid));
        $lead =PersonalQuote::where('uuid', $this->quoteUuid)->first();
        LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::CYBER_OCB_INTRO_EMAIL);
        if (! $lead) {
            LoggerService::info(self::class." - Lead not found for uuid: {$this->quoteUuid}");

            return;
        }
        
        
    }
}
