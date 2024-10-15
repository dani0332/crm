<?php

namespace App\Jobs;

use App\Enums\QuoteTypes;
use App\Services\SendEmailCustomerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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

        $this->quoteType = $this->quoteType ?: QuoteTypes::TRAVEL;

        $lead = $this->quoteType?->model()::where('uuid', $this->uuid)->first();
        if ($lead) {
            info(self::class.' - Lead found for uuid : '.$lead->uuid);
        } else {
            info(self::class.' - Lead not found for uuid : '.$this->uuid);

            return;
        }

        if (empty($lead->advisor_id)) {
            if ($this->quoteType === QuoteTypes::TRAVEL) {
                $sendEmailCustomerService->sendSICDedicatedEmail($lead, $this->quoteType);
            }
        } else {
            info('SICFollowupEmailJob - Lead Advisor Available - Ref ID: '.$lead->uuid.'- Time: '.now());
        }
    }
}
