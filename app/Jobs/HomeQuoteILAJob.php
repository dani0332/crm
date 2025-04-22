<?php

namespace App\Jobs;

use App\Enums\QuoteTypes;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HomeQuoteILAJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 300;

    /**
     * The quote UID to process.
     */
    protected string $quoteUID;

    /**
     * Create a new job instance.
     */
    public function __construct(string $quoteUID)
    {
        $this->quoteUID = $quoteUID;
    }

    /**
     * Execute the job.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        info('HomeQuoteILAJob started', ['quoteUID' => $this->quoteUID]);

        try {
            if (empty($this->quoteUID)) {
                throw new Exception('Invalid quote UID provided.');
            }

            $response = QuoteTypes::HOME->allocate($this->quoteUID);
            $assignedAdvisorId = $response['advisorId'] ?? '';

            if (! empty($assignedAdvisorId)) {
                info('Quote allocation executed successfully.', [
                    'quoteUID' => $this->quoteUID,
                    'assignedAdvisorId' => $assignedAdvisorId,
                ]);
            } else {
                info('Quote allocation executed successfully, but no advisor was allocated.', [
                    'quoteUID' => $this->quoteUID,
                    'assignedAdvisorId' => 'No advisor allocated',
                ]);
            }
        } catch (Exception $e) {
            info('Error during home quote allocation.', [
                'quoteUID' => $this->quoteUID,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        info('HomeQuoteILAJob failed', [
            'quoteUID' => $this->quoteUID,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
