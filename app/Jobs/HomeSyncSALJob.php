<?php

namespace App\Jobs;

use App\Services\HomeQuoteService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class HomeSyncSALJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = 300;
    private array $requestData;

    public function __construct(array $requestData)
    {
        $this->requestData = $requestData;
    }

    public function handle(): void
    {
        Log::info('Processing SAL sync job.', ['quoteUID' => $this->requestData['quoteUID']]);

        try {
            $request = new Request($this->requestData);

            $response = app(HomeQuoteService::class)->syncSAL($request);

            Log::info('SAL sync completed successfully.', ['quoteUID' => $this->requestData['quoteUID']]);
        } catch (Exception $e) {
            Log::error('SAL sync job failed.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'quoteUID' => $this->requestData['quoteUID'],
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('HomeSyncSALJob failed for quoteUID: '.($this->requestData['quoteUID'] ?? 'N/A'), [
            'error' => $exception->getMessage(),
        ]);
    }
}
