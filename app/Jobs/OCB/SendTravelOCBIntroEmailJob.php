<?php

namespace App\Jobs\OCB;

use App\Models\TravelQuote;
use App\Services\EmailServices\TravelEmailService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTravelOCBIntroEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 100;
    public $backoff = 300;
    public $quoteUuid;
    public $previousAdvisor;
    public $triggerSICWorkflow;
    public $handleZeroPlans;

    /**
     * Create a new job instance.
     */
    public function __construct($quoteUuid, $previousAdvisor = null, bool $triggerSICWorkflow = false, bool $handleZeroPlans = false)
    {
        $this->quoteUuid = $quoteUuid;
        $this->previousAdvisor = $previousAdvisor;
        $this->triggerSICWorkflow = $triggerSICWorkflow;
        $this->handleZeroPlans = $handleZeroPlans;
    }

    /**
     * Execute the job.
     */
    public function handle(TravelEmailService $travelEmailService): void
    {
        try {
            $lead = TravelQuote::where('uuid', $this->quoteUuid)->first();

            if (! $lead) {
                info(self::class." - Lead not found for uuid: {$this->quoteUuid}");

                return;
            }

            if (str_contains($lead->code, 'TRA-CAR-')) {
                info(self::class." - Lead is a CAR lead having Travel as EP, no need to send OCB INTRO email for uuid: {$this->quoteUuid}");

                return;
            }

            if ($lead->sic_flow_enabled) {
                info(self::class." - SIC workflow is enabled on this lead already for uuid: {$this->quoteUuid}");

                return;
            } else {
                info(self::class." - Lead found for uuid: {$this->quoteUuid}");

                $responseCode = $travelEmailService->sendTravelOCBIntroEmail($lead, $this->previousAdvisor, $this->triggerSICWorkflow, $this->handleZeroPlans);
                if (in_array($responseCode, [200, 201])) {
                    info(self::class." - OCB INTRO Email Sent: {$responseCode} Customer Email Address: {$lead->email} Quote UuId: {$this->quoteUuid}");
                } else {
                    Log::error(self::class." - OCB INTRO Email Not Sent: {$responseCode} Customer EmailAddress: {$lead->email}");
                }
            }
        } catch (Exception $e) {
            Log::error(self::class." - Error: {$e->getMessage()} with stack trace {$e->getTraceAsString()}");
        }
    }
}
