<?php

namespace App\Listeners;

use App\Events\HealthQuoteAdvisorUpdated;
use App\Services\HealthEmailService;

class HandleHealthAdvisorUpdated
{
    /**
     * Create the event listener.
     */
    public function __construct(public HealthEmailService $healthEmailService) {}

    /**
     * Handle the event.
     */
    public function handle(HealthQuoteAdvisorUpdated $event): void
    {
        info(self::class." initiated for uuid: {$event->lead?->uuid}");
        $this->healthEmailService->initiateApplyNowEmail($event->lead);
    }
}
