<?php

namespace App\Jobs;

use App\Services\BridgerInsightService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BridgerDecisionUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 40;
    public $backoff = 360;
    private $amlResultId;
    private $amlDecision;
    private $bridgerAPIToken;

    /**
     * Create a new job instance.
     */
    public function __construct($bridgerAPIToken, $amlResultId, $amlDecision)
    {
        $this->bridgerAPIToken = $bridgerAPIToken;
        $this->amlResultId = $amlResultId;
        $this->amlDecision = $amlDecision;
    }

    /**
     * Execute the job.
     */
    public function handle(BridgerInsightService $bridgerInsightService): void
    {
        try {
            info('BridgerDecisionUpdateJob - AML Log Data Update with Result ID : '.$this->amlResultId.' - Decision : '.$this->amlDecision);
            $bridgerInsightService->updateDecisionOnLexisNexis($this->bridgerAPIToken, $this->amlResultId, $this->amlDecision);
        } catch (\Exception $exception) {
            info('BridgerDecisionUpdateJob Exception: '.$exception->getMessage());
            Log::error($exception);
        }
    }
}
