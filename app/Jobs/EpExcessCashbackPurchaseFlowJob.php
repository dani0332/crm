<?php

namespace App\Jobs;

use App\Services\EpExcessCashbackService;
use App\Services\Logger\LoggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class EpExcessCashbackPurchaseFlowJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 300;
    public $backoff = 180;

    private string $quoteId;
    private int $quoteTypeId;
    private int $etId;

    private string $logPrefix = 'EpExcessCashback - PurchaseFlowJob:';
    private array $logExtra = [];

    /**
     * Create a new job instance.
     */
    public function __construct(string $quoteId, int $quoteTypeId, int $etId)
    {
        $this->quoteId = $quoteId;
        $this->quoteTypeId = $quoteTypeId;
        $this->etId = $etId;

        $this->logExtra = [
            'quoteId' => $this->quoteId,
            'quoteTypeId' => $this->quoteTypeId,
            'etId' => $this->etId,
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        LoggerService::info($this->logPrefix . ' Job started', extra: $this->logExtra);

        $epEcbService = new EpExcessCashbackService($this->quoteId, $this->quoteTypeId, $this->etId);

        // Service will throw exceptions on failure, which the job retry mechanism will catch
        $epEcbService->processPurchaseFlow();

        LoggerService::info($this->logPrefix . ' Job completed successfully', extra: $this->logExtra);
    }

    /**
     * Handle a job failure (called after all retry attempts are exhausted)
     */
    public function failed(Throwable $exception): void
    {
        LoggerService::error($this->logPrefix . ' Job failed after all retries', extra: [
            ...$this->logExtra,
            'error' => $exception->getMessage()
        ]);
    }
}
