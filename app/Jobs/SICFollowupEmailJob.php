<?php

namespace App\Jobs;

use App\Facades\Ken;
use App\Models\CarQuote;
use App\Services\SendEmailCustomerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SICFollowupEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    private $uuid;

    public $tries = 3;
    public $timeout = 15;
    public $backoff = 60;

    public function __construct($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * Execute the job.
     */
    public function handle(SendEmailCustomerService $sendEmailCustomerService)
    {
        $carLead = CarQuote::where('uuid', $this->uuid)->first();

        if (empty($carLead->advisor_id)) {
            $sendEmailCustomerService->sendSICFollowupEmail($carLead);
            $this->sendWhatsAppMessage();
        } else {
            info('SICFollowupEmailJob - Car Lead Advisor Available - Ref ID: '.$carLead->uuid.'- Time: '.now());
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
