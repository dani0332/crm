<?php

namespace App\Jobs;

use App\Models\PersonalQuote;
use App\Services\EmailServices\DeviceEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendDeviceAutomatedFollowupJob implements ShouldQueue
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
        $lead = PersonalQuote::where('uuid', $this->quoteUuid)->first();

        if (! $lead) {
            LoggerService::info('SendDeviceAutomatedFollowupJob - Lead not found ');

            return;
        }
        LoggerService::startQuoteLogging($lead);
        app(DeviceEmailService::class)->sendDeviceAutomatedFollowups($lead);
    }
}
