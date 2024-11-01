<?php

namespace App\Jobs\OCB;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\EmailServices\TravelEmailService;
use App\Models\TravelQuote;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SendOCBTravelRenewalIntroEmailJob implements ShouldQueue
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
    private function verifyPreChecks($lead)
    {
        if (! $lead) {
            info(self::class." - Lead not found for uuid: {$this->quoteUuid}");
            return false;
        }
        info(self::class." - Lead found for uuid: {$this->quoteUuid}");
        $shouldSkip =  Str::startsWith($lead->code, 'TRA-CAR-') || (empty($lead->advisor_id) && $lead->isMultiTrip());
        if ($shouldSkip) {
            if (Str::startsWith($lead->code, 'TRA-CAR-')) {
                info(self::class." - Lead is a CAR lead having Travel as EP, no need to send OCB INTRO email for uuid: {$lead->uuid}");
            } elseif (empty($lead->advisor_id) && $lead->isMultiTrip()) {
                info(self::class." - The Lead is Multi Trip Lead so Skipping Initial OCB Email for uuid: {$lead->uuid}");
            }
            return false;
        }

        return true;
    }
    public function handle(TravelEmailService $travelEmailService)
    {
        try {
            $lead = TravelQuote::where('uuid', $this->quoteUuid)->first();

            if (! $this->verifyPreChecks($lead)) {
                return;
            }
            $responseCode = $travelEmailService->SendOCBTravelRenewalIntroEmail($lead);
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
