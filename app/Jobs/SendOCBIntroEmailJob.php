<?php

namespace App\Jobs;

use App\Enums\TiersEnum;
use App\Models\CarQuote;
use App\Models\Tier;
use App\Services\CarEmailService;
use App\Services\HttpRequestService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOCBIntroEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 15;
    public $backoff = 300;
    public $quoteUuid;
    public $previousAdvisorId;
    /**
     * Create a new job instance.
     */
    public function __construct($quoteUuid, $previousAdvisorId)
    {
        $this->quoteUuid = $quoteUuid;
        $this->previousAdvisorId = $previousAdvisorId;
        $this->onQueue('lms');
    }

    /**
     * Execute the job.
     */
    public function handle(HttpRequestService $httpService, CarEmailService $carEmailService): void
    {
        try {

            $lead = CarQuote::where('uuid', $this->quoteUuid)->first();

            if (! $lead) {
                Log::info('SendOCBIntroEmailJob - Lead not found for uuid: '.$this->quoteUuid);

                return;
            } else {
                // Initialize email data and retrieve Tier R information
                $emailData = '';

                $tierR = Tier::where('name', TiersEnum::TIER_R)->where('is_active', 1)->first();

                // Retrieve plans with available ratings for the given lead
                $plans = $httpService->getPlans($lead->uuid, false, false, false);

                $responseCode = $carEmailService->sendCarOCBIntroEmail($plans, $lead, $tierR);

                if (in_array($responseCode, [200, 201])) {
                    info('SendOCBIntroEmailJob - OCB INTRO Email Sent: '.$responseCode.' Customer Email Address: '.$lead->email.' Quote UuId: '.$this->quoteUuid);
                } else {
                    Log::error('SendOCBIntroEmailJob - OCB INTRO Email Not Sent: '.$responseCode.' Customer EmailAddress:'.$lead->email);
                }
            }

        } catch (Exception $e) {
            Log::info('SendOCBIntroEmailJob - Error: '.$e->getMessage());
        }
    }
}
