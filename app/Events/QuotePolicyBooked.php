<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuotePolicyBooked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string  $quoteUID  Quote UUID
     * @param  int  $quoteTypeId  Quote Type ID
     * @param  string  $eventType  Event type (default: Purchase)
     */
    public function __construct(
        public readonly string $quoteUID,
        public readonly int $quoteTypeId,
        public readonly string $eventType = 'Purchase'
    ) {}
}
