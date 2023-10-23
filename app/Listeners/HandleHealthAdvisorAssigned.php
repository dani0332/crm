<?php

namespace App\Listeners;

use App\Events\HealthAdvisorAssigned;
use App\Services\HealthQuoteService;
use Carbon\Carbon;

class HandleHealthAdvisorAssigned
{
    protected $healthQuoteService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(HealthQuoteService $healthService)
    {
        $this->healthQuoteService = $healthService;
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(HealthAdvisorAssigned $event)
    {
        info('With in AdvisorAssignedListener');
        if ($event->lead) {
            $lead = $event->lead;
            info('Hit the Plans api for '.$lead->uuid);
            $this->healthQuoteService->getQuotePlans($lead->uuid);
            info('Updating Quote Updated at');
            $lead->quote_updated_at = Carbon::now();
            $lead->save();
        }
    }
}
