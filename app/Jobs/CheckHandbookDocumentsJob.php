<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckHandbookDocumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes
    private $quoteType;

    /**
     * Create a new job instance.
     *
     * @param  string  $quoteType  The type of quote to check (Car, Health, Travel)
     */
    public function __construct($quoteType)
    {
        $this->quoteType = $quoteType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            LoggerService::info("Starting handbook documents check for {$this->quoteType}", [
                'quote_type' => $this->quoteType,
                'job_id' => $this->job->getJobId(),
            ]);

            $quoteDocumentService = app(QuoteDocumentService::class);
            $quoteDocumentService->checkHandbookDocuments($this->quoteType);

            LoggerService::info("Completed handbook documents check for {$this->quoteType}", [
                'quote_type' => $this->quoteType,
                'job_id' => $this->job->getJobId(),
            ]);
        } catch (\Exception $e) {
            LoggerService::error("Failed to check handbook documents for {$this->quoteType}", [
                'quote_type' => $this->quoteType,
                'error' => $e->getMessage(),
                'job_id' => $this->job->getJobId(),
            ]);

            throw $e; // Re-throw to trigger job retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        LoggerService::error('Handbook documents check job failed ', [
            'quote_type' => $this->quoteType,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['handbook-check', $this->quoteType];
    }
}
