<?php

namespace App\Jobs;

use App\Models\HealthQuote;
use App\Services\HealthEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\Skip;
use Illuminate\Queue\SerializesModels;

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
        LoggerService::startQuoteLogging($lead);
        if (! $lead) {
            LoggerService::info('SendHealthSICWAFollowupJob - Lead not found ');

            return;
        }

        app(HealthEmailService::class)->sendSICHealthFollowupsWA($lead);

    }

    public function middleware()
    {
        $healthQuote = HealthQuote::where('uuid', $this->quoteUuid)->first();
        $isAUHLead = $healthQuote?->isAUHLead() && $healthQuote?->isLeadSourceRevivalOrInsuranceWallet();

        if ($isAUHLead) {
            LoggerService::info(self::class." - Skipping SIC WA Followup Email because lead is from AUH and Revival/Insurance Wallet for uuid: {$this->quoteUuid}");
        }

        return [
            Skip::when(fn () => $isAUHLead),
        ];
    }
}
