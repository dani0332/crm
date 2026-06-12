<?php

namespace App\Jobs\Claim;

use App\Models\ClaimRequest;
use App\Services\EmailServices\ClaimEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ClaimIntroEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 60;
    public $backoff = 30;
    private string $claimUuid;
    /**
     * Create a new job instance.
     */
    public function __construct(string $claimUuid)
    {
        $this->claimUuid = $claimUuid;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $claim = ClaimRequest::where('uuid', $this->claimUuid)->first();
        LoggerService::startQuoteLogging($this->claimUuid);

        if (! $claim) {
            LoggerService::error(self::class.' - Claim not found');

            return;
        }
        app(ClaimEmailService::class)->sendIntroEmail($claim);
        LoggerService::info(self::class.' - Claim intro email sent for claim');

    }
}
