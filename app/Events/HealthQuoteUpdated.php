<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\HealthQuote;
class HealthQuoteUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $healthQuote;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(HealthQuote $healthQuote)
    {
        $this->healthQuote = $healthQuote;
    }

}
