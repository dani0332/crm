<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFailedNonMotorRenewalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public int $backoff = 60;

    /**
     * @param  array<int, string>  $failedPolicyNumbers
     */
    public function __construct(
        public array $failedPolicyNumbers,
        public int $renewalsUploadLeadsId
    ) {}

    public function handle(): void
    {
        LoggerService::info(self::class.' - Non-motor CQF renewals errors', [
            'time' => now()->toIso8601String(),
            'renewals_upload_lead_id' => $this->renewalsUploadLeadsId,
            'failed_policy_numbers' => $this->failedPolicyNumbers,
        ]);
    }
}
