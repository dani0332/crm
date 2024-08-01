<?php

namespace App\Events;

use App\Models\PersonalQuote;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BikeQuoteAdvisorUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $lead;
    public $oldAdvisorId;
    public $oldAssignmentType;

    public function __construct(PersonalQuote $lead, $oldAdvisorId)
    {
        $this->lead = $lead;
        $this->oldAdvisorId = $oldAdvisorId;
    }
}
