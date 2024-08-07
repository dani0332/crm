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
                info('SendHealthOCBIntroEmailJob - Lead not found for uuid: '.$this->quoteUuid);
                return;
            }
            if ($lead->sic_flow_enabled) {
                info('SendHealthOCBIntroEmailJob - SIC work flow is enabled on this lead already : '.$this->quoteUuid);

                return;
            } else {
                info('SendHealthOCBIntroEmailJob - SIC work flow is not enabled on this lead : '.$this->quoteUuid);
                $responseCode = $healthEmailService->sendHealthOCBIntroEmail($lead,$healthQuoteService, $this->triggerSICWorkflow);
                if (in_array($responseCode, [200, 201])) {
                    info('SendHealthOCBIntroEmailJob - OCB INTRO Email Sent: '.$responseCode.' Customer Email Address: '.$lead->email.' Quote UuId: '.$this->quoteUuid);
                } else {
                    Log::error('SendHealthOCBIntroEmailJob - OCB INTRO Email Not Sent: '.$responseCode.' Customer EmailAddress:'.$lead->email);
                }
            }
        } catch (Exception $e) {
            info('SendHealthOCBIntroEmailJob - Error: '.$e->getMessage().' with stack trace: '.$e->getTraceAsString());
        }
    }

}
