<?php

namespace App\Jobs;

use App\Models\TravelQuote;
use App\Services\EmailServices\TravelEmailService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTravelAllianceFailedAllocationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public $tries = 3;

    public $timeout = 100;
    public $backoff = 300;
    private $quoteUuid;

    public function __construct($quoteUuid)
    {
        $this->quoteUuid = $quoteUuid;
    }

    /**
     * Execute the job.
     */
    public function handle(TravelEmailService $travelEmailService)
    {
        try {
            $lead = TravelQuote::where('uuid', $this->quoteUuid)->first();

            if (! $lead) {
                info(self::class." - Lead not found for uuid: {$this->quoteUuid}");

                return false;
            }
            $responseCode = $travelEmailService->sendTravelAllianceFailedAllocationEmail($lead);
            if (in_array($responseCode, [200, 201])) {
                info(self::class." - OCB INTRO Email Sent: {$responseCode} Customer Email Address: {$lead->email} Quote UuId: {$this->quoteUuid}");
            } else {
                Log::error(self::class." - OCB INTRO Email Not Sent: {$responseCode} Customer EmailAddress: {$lead->email} Quote UuId: {$this->quoteUuid}");
            }
        } catch (Exception $e) {
            Log::error(self::class." - Error: {$e->getMessage()} for uuid {$this->quoteUuid} with stack trace {$e->getTraceAsString()}");
        }
    }
}
