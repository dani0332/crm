<?php

namespace App\Jobs\OCB;

use App\Models\TravelQuote;
use App\Services\EmailServices\TravelEmailService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendTravelOCBIntroEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 100;
    public $backoff = 300;
    private $quoteUuid;
    private $previousAdvisor;
    private $triggerSICWorkflow;
    private $handleZeroPlans;
    private $forceSicWorkflow;

    /**
     * Create a new job instance.
     */
    public function __construct($quoteUuid, $previousAdvisor = null, bool $triggerSICWorkflow = false, bool $handleZeroPlans = false, bool $forceSicWorkflow = false)
    {
        $this->quoteUuid = $quoteUuid;
        $this->previousAdvisor = $previousAdvisor;
        $this->triggerSICWorkflow = $triggerSICWorkflow;
        $this->handleZeroPlans = $handleZeroPlans;
        $this->forceSicWorkflow = $forceSicWorkflow;
    }

    private function verifyPreChecks($lead)
    {
        if (! $lead) {
            info(self::class." - Lead not found for uuid: {$this->quoteUuid}");

            return false;
        }

        info(self::class." - Lead found for uuid: {$this->quoteUuid}");

        $shouldSkip = ($lead->sic_flow_enabled && ! $this->forceSicWorkflow) || Str::startsWith($lead->code, 'TRA-CAR-') || (empty($lead->advisor_id) && $lead->isMultiTrip());

        if ($shouldSkip) {
            if ($lead->sic_flow_enabled) {
                info(self::class." - SIC workflow is enabled on this lead already for uuid: {$lead->uuid}");
            } elseif (Str::startsWith($lead->code, 'TRA-CAR-')) {
                info(self::class." - Lead is a CAR lead having Travel as EP, no need to send OCB INTRO email for uuid: {$lead->uuid}");
            } elseif (empty($lead->advisor_id) && $lead->isMultiTrip()) {
                info(self::class." - The Lead is Multi Trip Lead so Skipping Initial OCB Email for uuid: {$lead->uuid}");
            }

            return false;
        }

        return true;
    }

    /**
     * Execute the job.
     */
    public function handle(TravelEmailService $travelEmailService): void
    {
        try {
            $lead = TravelQuote::where('uuid', $this->quoteUuid)->first();

            if (! $this->verifyPreChecks($lead)) {
                return;
            }

            $responseCode = $travelEmailService->sendTravelOCBIntroEmail($lead, $this->previousAdvisor, $this->triggerSICWorkflow, $this->handleZeroPlans, $this->forceSicWorkflow);
            if (in_array($responseCode, [200, 201])) {
                info(self::class." - OCB INTRO Email Sent: {$responseCode} Customer Email Address: {$lead->email} Quote UuId: {$this->quoteUuid}");
            } else {
                Log::error(self::class." - OCB INTRO Email Not Sent: {$responseCode} Customer EmailAddress: {$lead->email} Quote UuId: {$this->quoteUuid}");
            }
        } catch (Exception $e) {
            Log::error(self::class." - Error: {$e->getMessage()} for uuid {$this->quoteUuid} with stack trace {$e->getTraceAsString()}");
        }
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->quoteUuid))->dontRelease()];
    }
}
