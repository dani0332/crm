<?php

namespace App\Jobs;

use App\Models\HealthQuote;
use App\Services\HealthEmailService;
use App\Services\HealthQuoteService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendHealthOCBIntroEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $quoteUuid;
    protected $healthQuoteService;
    private $previousAdvisor;
    private $triggerSICWorkflow;
    public $tries = 3;
    public $timeout = 60;
    public $backoff = 10;
    /**
     * Create a new job instance.
     */
    public function __construct($quoteUuid, $previousAdvisor, $triggerSICWorkflow = false)
    {
        $this->quoteUuid = $quoteUuid;
        $this->previousAdvisor = $previousAdvisor;
        $this->triggerSICWorkflow = $triggerSICWorkflow;
    }
    /**
     * Execute the job.
     */
    public function handle(HealthEmailService $healthEmailService, HealthQuoteService $healthQuoteService): void
    {
        try {
            $lead = HealthQuote::where('uuid', $this->quoteUuid)->first();
        
            if (! $lead) {
                info("SendHealthOCBIntroEmailJob - Lead not found for UUID: {$this->quoteUuid}");
                return;
            }
        
            if ($lead->sic_flow_enabled) {
                info("SendHealthOCBIntroEmailJob - SIC workflow is already enabled for UUID: {$this->quoteUuid}");
                return;
            }
        
            info("SendHealthOCBIntroEmailJob - SIC workflow is not enabled for UUID: {$this->quoteUuid}");
            $responseCode = $healthEmailService->sendHealthOCBIntroEmail($lead, $healthQuoteService, $this->triggerSICWorkflow);
        
            $logMessage = in_array($responseCode, [200, 201])
                ? "OCB INTRO Email Sent"
                : "OCB INTRO Email Not Sent";
        
            info("SendHealthOCBIntroEmailJob - {$logMessage}: {$responseCode} | Email: {$lead->email} | UUID: {$this->quoteUuid}");
        } catch (Exception $e) {
            Log::error("SendHealthOCBIntroEmailJob - Exception: {$e->getMessage()} | Stack Trace: {$e->getTraceAsString()}");
        }
    }
}