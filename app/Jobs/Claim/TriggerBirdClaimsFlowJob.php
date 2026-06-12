<?php

declare(strict_types=1);

namespace App\Jobs\Claim;

use App\Models\ClaimRequest;
use App\Services\ClaimsService;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatches the Bird claims flow asynchronously after a claim policy number change.
 *
 * Decoupled from the observer's updating event so the synchronous Ken HTTP request
 * never executes inside an open DB transaction.
 */
class TriggerBirdClaimsFlowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public int $backoff = 30;

    public function __construct(private readonly string $claimUuid)
    {
        $this->afterCommit();
    }

    public function handle(ClaimsService $claimsService): void
    {
        $claim = ClaimRequest::where('uuid', $this->claimUuid)->first();

        if (! $claim) {
            LoggerService::warning(self::class.' - Claim not found, skipping Bird claims flow', [
                'claim_uuid' => $this->claimUuid,
            ]);

            return;
        }

        $claimsService->triggerBirdClaimsFlow($claim);

        LoggerService::info(self::class.' - Bird claims flow triggered successfully', [
            'claim_uuid' => $this->claimUuid,
        ]);
    }

    public function failed(Exception $exception): void
    {
        LoggerService::error(self::class.' - Job permanently failed after all retries', [
            'claim_uuid' => $this->claimUuid,
            'error' => $exception->getMessage(),
        ]);
    }
}
