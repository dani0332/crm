<?php

namespace App\Jobs;

use App\Enums\InstantChatReportsEnum;
use App\Services\InstantAlfredExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class InstantAlfredExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes - sufficient for optimized streaming approach
    public $tries = 2;

    public function __construct(private array $params)
    {
        $this->onQueue('renewals');

        if (in_array(config('app.env'), ['production', 'uat', 'staging'])) {
            Log::warning('InstantAlfredExportJob: Horizon timeout for renewals queue is 60s, but job timeout is 600s. Large exports (>10k records) may fail due to Horizon timeout.', [
                'horizon_timeout' => 60,
                'job_timeout' => $this->timeout,
                'recipient' => $params['recipientEmail'] ?? 'unknown',
            ]);
        }
    }

    public function handle(InstantAlfredExportService $service): void
    {
        $jobStartTime = microtime(true);
        $reportType = $this->params['report'] ?? InstantChatReportsEnum::DETAILED_REPORT;

        Log::info('InstantAlfredExportJob started', [
            'recipient' => $this->params['recipientEmail'],
            'report' => $reportType,
            'job_timeout' => $this->timeout,
            'queue' => 'renewals',
        ]);

        try {
            if ($reportType === InstantChatReportsEnum::CONSOLIDATED_REPORT) {
                $result = $service->exportConsolidatedAndEmail($this->params);
            } else {
                $result = $service->exportAndEmail($this->params);
            }

            $jobDuration = round(microtime(true) - $jobStartTime, 2);
            Log::info('InstantAlfredExportJob completed', array_merge($result, [
                'job_duration' => $jobDuration,
            ]));
        } catch (\Throwable $e) {
            $jobDuration = round(microtime(true) - $jobStartTime, 2);
            Log::error('InstantAlfredExportJob error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'job_duration' => $jobDuration,
                'job_timeout' => $this->timeout,
                'recipient' => $this->params['recipientEmail'] ?? 'unknown',
                'report' => $reportType,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('InstantAlfredExportJob failed', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'recipient' => $this->params['recipientEmail'] ?? 'unknown',
        ]);
    }
}
