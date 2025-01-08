<?php

namespace App\Events\Health;

use App\Models\HealthQuote;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HealthTransactionApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $healthQuote;

    public function __construct(HealthQuote $healthQuote)
    {
        $this->healthQuote = $healthQuote;
    }
}
