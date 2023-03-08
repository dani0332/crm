<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CarQuoteUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $updatedAt;
    public $updatedBy;

    public function __construct($updatedAt, $updatedBy)
    {
        $this->updatedAt = $updatedAt;
        $this->updatedBy = $updatedBy;
    }
}
