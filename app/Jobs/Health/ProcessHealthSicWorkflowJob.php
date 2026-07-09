<?php

namespace App\Jobs\Health;

use App\Facades\Capi;
use App\Models\HealthQuote;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessHealthSicWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 360;

    /**
     * Create a new job instance.
     */
    public function __construct(public HealthQuote $healthQuote) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = [
            'quoteUID' => $this->healthQuote->uuid,
            'pqAdvisorId' => $this->healthQuote->pq_advisor_id,
        ];

        LoggerService::info(self::class.' - Sending process health SIC workflow request to CAPI', extra: $data);

        $response = Capi::request('/api/v1-process-health-sic-workflow', 'post', $data);

        LoggerService::info(self::class.' - CAPI process health SIC workflow response', extra: [
            'uuid' => $this->healthQuote->uuid,
            'response' => $response,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        LoggerService::error(self::class.' - CAPI process health SIC workflow job failed', [
            'uuid' => $this->healthQuote->uuid,
        ], $e);
    }
}
