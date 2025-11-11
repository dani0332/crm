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
use App\Services\EmailServices\CyberEmailService;

class SendCyberOCBIntroEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 3;
    public $timeout = 40;
    public $backoff = 300;
    private $quoteUuid;
    private $previousAdvisor;
    private $handleZeroPlans;
    private $triggerSICWorkflow;
    private $triggerOnlyWorkflow;
    private $forceSicWorkflow;
    private $quoteType;

    /**
     * Create a new job instance.
     */
    public function __construct($quoteUuid, $previousAdvisor = null, bool $triggerSICWorkflow = false, bool $handleZeroPlans = false, bool $forceSicWorkflow = false)
    { 
        $this->quoteUuid = $quoteUuid;
        $this->forceSicWorkflow = $forceSicWorkflow;
        $this->triggerSICWorkflow = $triggerSICWorkflow;
        $this->previousAdvisor = $previousAdvisor;
        $this->handleZeroPlans = $handleZeroPlans;
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // LoggerService::startQuoteLogging(QuoteTypes::getName($this->quoteType)->refId($this->quoteUuid));
        $lead =PersonalQuote::where('uuid', $this->quoteUuid)->first();
        LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::CYBER_OCB_INTRO_EMAIL);
        if (! $lead) {
            LoggerService::info(self::class." - Lead not found for uuid: {$this->quoteUuid}");

            return;
        }
        app(CyberEmailService::class)->sendCyberOCBIntroEmail($lead);
        LoggerService::info(self::class." - OCB Intro Email sent for uuid: {$this->quoteUuid}");
        LoggerService::endLogging();

    }
}

   
