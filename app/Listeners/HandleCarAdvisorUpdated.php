<?php

namespace App\Listeners;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypes;
use App\Events\CarQuoteAdvisorUpdated;
use App\Jobs\OCB\SendCarOCBIntroEmailJob;
use App\Jobs\SendFTCEmailJob;
use App\Models\ApplicationStorage;
use App\Models\Customer;
use App\Models\User;
use App\Services\CarAllocationService;
use App\Services\EmailServices\CarEmailService;
use App\Services\HttpRequestService;
use App\Services\SendSmsCustomerService;
use App\Services\SIBService;
use App\Services\UserService;
use Illuminate\Support\Facades\Log;

class HandleCarAdvisorUpdated
{
    protected $carQuoteService;
    protected $smsService;
    protected $carEmailService;
    protected $httpService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(CarAllocationService $carQuoteService, SendSmsCustomerService $smsService, CarEmailService $carEmailService, HttpRequestService $httpService)
    {
        $this->carQuoteService = $carQuoteService;
        $this->smsService = $smsService;
        $this->carEmailService = $carEmailService;
        $this->httpService = $httpService;
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(CarQuoteAdvisorUpdated $event)
    {
        info('inside handle car update advisor');

        $lead = $event->lead;

        if ($lead) {
            SendFTCEmailJob::dispatch($lead->uuid, QuoteTypes::CAR)->delay(now()->addSeconds(5));
        }

        $skippableSources = [LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::INSLY];
        if (in_array($lead->source, $skippableSources)) {
            info('lead is source is '.$lead->source.' upload. Skipping intro email job');

            return;
        }

        $oldAdvisorId = $event->oldAdvisorId;

        $previousAdvisor = User::where('id', $oldAdvisorId)->first();

        info('about to trigger intro email job for lead uuid : '.$lead->uuid.' and previous advisor id : '.$oldAdvisorId);

        if ($lead->sic_flow_enabled) {

            info('Lead is SIC enabled so send SIC notification to advisor against: '.$lead->uuid);
            $user = (new UserService)->getUserById($lead->advisor_id);
            $responseCode = $this->carEmailService->sendSICNotificationToAdvisor($lead, $user);

            if (in_array($responseCode, [200, 201])) {
                info('SIC Notification to Advisor: '.$user->email.' Sent Successfully against Quote UuId: '.$lead->uuid);
            } else {
                Log::error('SIC Notification to Advisor Not Sent: '.$responseCode.' Advisor EmailAddress: '.$user->email.' Quote UuId: '.$lead->uuid);
            }

            $lead->sic_flow_enabled = 0;
            $lead->save();
            info('SIC flow is disabled for lead uuid : '.$lead->uuid);

            // We need to trigger stop workflow event for SIC if the lead is in SIC workflow
            $sicEventName = ApplicationStorage::where('key_name', 'SIC_END_WORKFLOW_NAME')->first();
            if ($sicEventName) {
                SIBService::createWorkflowEvent($sicEventName->value, $lead);
                info('SIC workflow stopped for lead uuid : '.$lead->uuid);
            } else {
                info('SIC workflow key not found');
            }
        }

        SendCarOCBIntroEmailJob::dispatch($lead->uuid, $previousAdvisor);

        info('SMS sending code reached');
    }
    public function buildSMS($lead)
    {
        info('inside build sms');
        $content = 'Hi ';
        $clientNumber = '+923340555850';
        $customer = Customer::where('id', 19811)->first();
        $this->smsService->sendSMS($clientNumber, $content, $customer);

        info('inside after build sms');
    }
}
