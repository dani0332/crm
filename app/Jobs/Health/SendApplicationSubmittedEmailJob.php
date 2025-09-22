<?php

namespace App\Jobs\Health;

use App\Models\HealthQuote;
use App\Services\HealthEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\Skip;
use Illuminate\Queue\SerializesModels;

class SendApplicationSubmittedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public HealthQuote $healthQuote)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        info(self::class." - Going to Send Application Submitted Email Job for UUID: {$this->healthQuote->uuid}");
        app(HealthEmailService::class)->sendApplicationSubmittedEmail($this->healthQuote);
    }

    public function middleware()
    {
        $isAUHAndRevivalOrInsuranceWallet = $this->healthQuote?->isAUHLead() || ($this->healthQuote?->isAUHLead(false) && $this->healthQuote?->isLeadSourceRevivalOrInsuranceWallet());

        if ($isAUHAndRevivalOrInsuranceWallet) {
            LoggerService::info(self::class." - Skipping Application Submitted Email because lead is from AUH or AUH and Revival/Insurance Wallet for uuid: {$this->healthQuote->uuid}");
        }

        return [
            Skip::when(fn () => $isAUHAndRevivalOrInsuranceWallet),
        ];
    }
}
