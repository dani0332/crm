<?php

namespace App\Events;

use App\Models\TravelQuote;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TravelQuoteAdvisorUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $lead;
    public $oldAdvisorId;

    /**
     * Create a new event instance.
     */
    public function __construct(TravelQuote $lead, $oldAdvisorId)
    {
        $this->lead = $lead;
        $this->oldAdvisorId = $oldAdvisorId;
    }
}
