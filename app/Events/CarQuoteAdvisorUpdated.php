<?php

namespace App\Events;

use App\Models\CarQuote;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CarQuoteAdvisorUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $lead;

    public function __construct(CarQuote $lead)
    {
        $this->lead = $lead;
    }
}
