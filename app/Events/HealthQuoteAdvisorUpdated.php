<?php

namespace App\Events;

use App\Models\HealthQuote;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HealthQuoteAdvisorUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $lead;
    public $oldAdvisorId;

    /**
     * Create a new event instance.
     */
    public function __construct(HealthQuote $lead, $oldAdvisorId)
    {
        $this->lead = $lead;
        $this->oldAdvisorId = $oldAdvisorId;
    }
}
