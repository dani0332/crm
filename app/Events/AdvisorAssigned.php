<?php

namespace App\Events;

use App\Models\CarQuote;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdvisorAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $lead;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(CarQuote $lead)
    {
        $this->lead = $lead;
    }
}
