<?php

namespace App\Listeners;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Events\TravelQuoteAdvisorUpdated;
use App\Jobs\OCB\SendTravelOCBIntroEmailJob;
use App\Models\TravelQuote;
use App\Models\User;
use App\Services\EmailServices\TravelEmailService;
use App\Services\HttpRequestService;
use App\Services\SendSmsCustomerService;
use App\Services\SIBService;
use App\Services\TravelAllocationService;
use App\Services\UserService;
use Illuminate\Support\Facades\Log;

class HandleTravelAdvisorUpdated
{
    /**
     * Create the event listener.
     */
    public function __construct(public TravelAllocationService $travelAllocationService, public SendSmsCustomerService $smsService, public TravelEmailService $travelEmailService, public HttpRequestService $httpService)
    {
    }

    /**
     * Handle the event.
     */
    public function handle(TravelQuoteAdvisorUpdated $event): void
    {
        info(self::class.' - inside handle travel update advisor');

        $lead = $event->lead;

        if ($lead->source == LeadSourceEnum::RENEWAL_UPLOAD) {
            info(self::class.' - lead is source is renewal upload. Skipping intro email job');

            return;
        }

        $oldAdvisorId = $event->oldAdvisorId;

        $previousAdvisor = User::where('id', $oldAdvisorId)->first();

        info(self::class." - about to trigger intro email job for lead uuid : {$lead->uuid} and previous advisor id : {$oldAdvisorId}");

        if ($lead->sic_flow_enabled) {
            info(self::class." - Lead is SIC enabled so send SIC notification to advisor against: {$lead->uuid}");
            $user = (new UserService)->getUserById($lead->advisor_id);
            $responseCode = $this->travelEmailService->sendSICNotificationToAdvisor($lead, $user);

            if (in_array($responseCode, [200, 201])) {
                info(self::class." - SIC Notification to Advisor: {$user->email} Sent Successfully against Quote UuId: {$lead->uuid}");
            } else {
                Log::error(self::class." - SIC Notification to Advisor Not Sent: {$responseCode} Advisor EmailAddress: {$user->email} Quote UuId: {$lead->uuid}");
            }

            TravelQuote::withoutEvents(function () use ($lead) {
                $lead->update(['sic_flow_enabled' => 0]);
            });
            info(self::class." - SIC flow is disabled for lead uuid : {$lead->uuid}");

            // We need to trigger stop workflow event for SIC if the lead is in SIC workflow
            $sicEventName = getAppStorageValueByKey(ApplicationStorageEnums::SIC_TRAVEL_WORKFLOW_DISABLE);
            if ($sicEventName) {
                SIBService::createWorkflowEvent($sicEventName, $lead);
                info(self::class." - SIC workflow stopped for lead uuid : {$lead->uuid}");
            } else {
                info(self::class.' - SIC workflow key not found');
            }
        }

        info(self::class.' - Going to dispatch SendTravelOCBIntroEmailJob ................');
        SendTravelOCBIntroEmailJob::dispatch($lead->uuid, $previousAdvisor);
    }
}
