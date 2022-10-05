<?php

namespace App\Listeners;

use App\Events\AdvisorAssigned;
use App\Services\HealthQuoteService;
use Carbon\Carbon;

class AdvisorAssignedListener
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
     * @param  \App\Events\AdvisorAssigned  $event
     * @return void
     */
    public function handle(AdvisorAssigned $event)
    {
        if($event->lead) {
            $lead = $event->lead;
            $response = $this->healthQuoteService->getQuotePlans($lead->uuid);
            if (gettype($response) != 'string') { 
                $lead->quote_updated_at = Carbon::now();
                $lead->save();
            }
        }
    }
}
