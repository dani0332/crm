<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Job to retrieve policy documents from NGI provider with FRD-compliant retry logic.
 *
 * FRD Requirements:
 * - Initial delay: 3 minutes after policy creation (handled by dispatch delay)
 * - Retry: up to 3 times with 5-minute gaps
 *
 * Implements ShouldBeUnique (not ShouldBeUniqueUntilProcessing) so the unique lock is held
 * until the job finishes. UntilProcessing releases the lock before handle(), which allows a
 * second dispatch to queue another job while the first worker is still running.
 */
class NgiGetPolicyDocumentsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of attempts: 1 initial + 3 retries = 4 total (per FRD)
     */
    public int $tries = 4;

    /**
     * Timeout for each attempt (seconds) - API call + document downloads
     */
    public int $timeout = 120;

    /**
     * Backoff between retries: 5 minutes = 300 seconds (per FRD)
     */
    public int $backoff = 300;

    /**
     * Cache lock TTL for unique dispatch (seconds). Must cover initial delay plus worst-case
     * retries: DOCUMENT_FETCH_DELAY + tries * timeout + (tries - 1) * backoff, with headroom.
     */
    public int $uniqueFor = 7200;

    private int $processId;

    public function __construct(int $processId)
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::POLICY_ISSUANCE_DOWNLOAD_UPLOAD_DOCUMENTS_JOB);
        $this->processId = $processId;
        $this->onQueue('policy-issuance-automation');
    }

    /**
     * Unique identifier for preventing duplicate jobs
     */
    public function uniqueId(): string
    {
        return 'ngi-get-policy-docs-'.$this->processId;
    }

    /**
     * Execute the job
     */
    public function handle(NgiGetPolicyDocumentsService $service): void
    {
        $service->execute($this->processId, $this->attempts(), $this->tries);
    }

    /**
     * Handle job failure after all retries exhausted
     */
    public function failed(Throwable $exception): void
    {
        app(NgiGetPolicyDocumentsService::class)->handleFailure(
            $this->processId,
            $this->attempts(),
            $this->tries,
            $exception->getMessage()
        );
    }
}
