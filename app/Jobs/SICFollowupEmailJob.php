<?php

namespace App\Jobs;

use App\Enums\QuoteTypes;
use App\Facades\Ken;
use App\Services\SendEmailCustomerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Enums\ApplicationStorageEnums;
use App\Services\BirdService;
use App\Enums\WorkflowTypeEnum;

class SICFollowupEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public $tries = 3;

    public $timeout = 60;
    public $backoff = 60;
    private $uuid;
    private $quoteType;

    public function __construct($uuid, QuoteTypes $quoteType)
    {
        $this->uuid = $uuid;
        $this->quoteType = $quoteType;
    }

    /**
     * Execute the job.
     */
    public function handle(SendEmailCustomerService $sendEmailCustomerService)
    {
        info(self::class.' - Inside handle', [
            'uuid' => $this->uuid,
            'quoteType' => $this->quoteType,
        ]);

        $this->quoteType = $this->quoteType ?: QuoteTypes::CAR;

        $lead = $this->quoteType?->model()::where('uuid', $this->uuid)->first();
        if ($lead) {
            info(self::class.' - Lead found for uuid : '.$lead->uuid);
        } else {
            info(self::class.' - Lead not found for uuid : '.$this->uuid);

            return;
        }

        if (empty($lead->advisor_id)) {
            if ($this->quoteType === QuoteTypes::CAR) {
                $sendEmailCustomerService->sendSICFollowupEmail($lead, $this->quoteType);
                $this->sendWhatsAppMessage();
            } else {
                $url = match ($this->quoteType) {
                    QuoteTypes::TRAVEL => getAppStorageValueByKey(ApplicationStorageEnums::BIRD_TRAVEL_FLLOWUP_DEDICATED_WORKFLOW_URL),
                };
                $data = [
                    'customerEmail' => $lead->email,
                    'customerName' => "{$lead->first_name} {$lead->last_name}",
                    'customerMobile' => (! empty($lead->mobile_no) ? $lead->mobile_no : ''),
                    'instantAlfredLink' => $this->quoteType->quoteLink($lead->uuid, ['IA' => 'true']),
                    'quoteUUID' => $lead->uuid,
                    'requestForAdvisor' => $this->quoteType->quoteLink($lead->uuid, ['assignAdvisor' => 'true']),
                    'quoteTypeId' => $this->quoteType->id(),
                    'quoteUUID' => $lead->uuid,
                    'refID' => $this->quoteType->quoteLink($lead->uuid),
                    'whatsappConsent' => getWhatsappConsent($this->quoteType, $lead->uuid),
                    'workflowType'=> WorkflowTypeEnum::TRAVEL_SIC_FOLLOWUPS,
                ];
                info("SICTravelFollowupEmailJob - Sending webhook request to: {$url} with Ref-ID: {$lead->uuid} | Time:".now());
                app(BirdService::class)->triggerWebHookRequest($url, (object) $data);
            }
        } else {
            info('SICFollowupEmailJob - Lead Advisor Available - Ref ID: '.$lead->uuid.'- Time: '.now());
        }
    }

    private function sendWhatsAppMessage()
    {
        try {
            $response = Ken::request('/send-sic-dedicated-wa', 'post', [
                'quoteUID' => $this->uuid,
            ]);
            info('SICFollowupEmailJob - '.now().' - sendWhatsAppMessage: response from ken| '.json_encode($response));
            if ($response) {
                info('Whatsapp message sent successfully. UUID: '.$this->uuid.' | Time: '.now());
            } else {
                info('invalid response from ken| UUID: '.$this->uuid.' | Time: '.now());
            }
        } catch (\Throwable $th) {
            Log::error('SICFollowupEmailJob - Error: '.$th->getMessage().' - UUID: '.$this->uuid.' - Time: '.now());
        }
    }
}
