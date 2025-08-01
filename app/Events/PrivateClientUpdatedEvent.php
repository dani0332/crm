<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrivateClientUpdatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The lead model instance.
     */
    public $lead;

    /**
     * The quote type ID.
     */
    public int $quoteTypeId;

    /**
     * Create a new event instance.
     */
    public function __construct(Model $lead, int $quoteTypeId)
    {
        $this->lead = $lead;
        $this->quoteTypeId = $quoteTypeId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('private-client-updated'),
        ];
    }
}
