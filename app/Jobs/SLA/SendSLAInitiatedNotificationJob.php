<?php

namespace App\Jobs\SLA;

use App\Models\SLATracking;
use App\Services\SLAService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSLAInitiatedNotificationJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(private SLATracking $slaRecord)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(SLAService $slaService): void
    {
        $isSent = $slaService->sendCallbackNotification($this->slaRecord);

        if (! $isSent && $this->attempts() < $this->tries) {
            $this->release(now()->addMinutes(2));
        }
    }
}
