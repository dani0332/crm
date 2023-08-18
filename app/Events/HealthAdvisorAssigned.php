<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HealthAdvisorAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $lead;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($lead)
    {
        $this->lead = $lead;
    }
}
