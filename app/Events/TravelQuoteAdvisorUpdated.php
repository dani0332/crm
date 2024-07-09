<?php

namespace App\Events;

use App\Models\TravelQuote;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TravelQuoteAdvisorUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public TravelQuote $lead, public $oldAdvisorId)
    {
    }
}
