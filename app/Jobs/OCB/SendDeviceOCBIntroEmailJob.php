<?php

namespace App\Jobs\OCB;

use App\Models\PersonalQuote;
use App\Services\EmailServices\DeviceEmailService;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class SendDeviceOCBIntroEmailJob implements ShouldQueue
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
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(DeviceEmailService $deviceEmailService): void
    {
        try {
            $lead = PersonalQuote::where('uuid', $this->quoteUuid)->first();

            if (! $this->verifyPreChecks($lead)) {
                return;
            }

            $responseCode = $deviceEmailService->sendDeviceOCBIntroEmail($lead);
            if (in_array($responseCode, [200, 201])) {
                LoggerService::info(self::class." - OCB INTRO Email Sent: {$responseCode} Customer Email Address: {$lead->email} Quote UuId: {$this->quoteUuid}");
            } elseif ($this->attempts() == $this->tries) {
                LoggerService::error(self::class." - OCB INTRO Email Not Sent: {$responseCode} Customer EmailAddress: {$lead->email} Quote UuId: {$this->quoteUuid}");
            }
        } catch (Exception $e) {
            LoggerService::error(self::class." - Error: {$e->getMessage()} for uuid {$this->quoteUuid} with stack trace {$e->getTraceAsString()}");
        }
    }
    private function verifyPreChecks($lead)
    {
        if (! $lead) {
            info(self::class." - Lead not found for uuid: {$this->quoteUuid}");

            return false;
        }

        info(self::class." - Lead found for uuid: {$this->quoteUuid}");

        return true;
    }
    public function middleware()
    {
        return [(new WithoutOverlapping($this->quoteUuid))->dontRelease()];
    }
}
