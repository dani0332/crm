<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Logger\LoggerService;
use App\Models\HealthQuote;
use App\Services\HealthEmailService;


class SendHealthSICWAFollowupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $quoteUuid;
    public $tries = 3;
    public $timeout = 90;
    public $backoff = 120;
    /**
     * Create a new job instance.
     */
    public function __construct($quoteUuid)
    {
        $this->quoteUuid = $quoteUuid;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lead = HealthQuote::where('uuid', $this->quoteUuid)->first();
        if(!$lead){
            LoggerService::info("SendHealthSICWAFollowupJob - Lead not found for UUID: {$this->quoteUuid}");
            return;
        }
        
        app(HealthEmailService::class)->sendSICHealthFollowupsWA($lead);
        
    
    }
}
