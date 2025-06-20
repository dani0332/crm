<?php

namespace App\Events;

use App\Enums\QuoteTypes;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(protected QuoteTypes $quoteType, protected string $uuid) {}

    public function broadcastOn()
    {
        return ['public.'.config('constants.APP_ENV').'.lead.status.updated'];
    }

    public function broadcastAs()
    {
        return 'lead.status.updated';
    }

    public function broadcastWith()
    {
        return [
            'quoteType' => $this->quoteType->value,
            'uuid' => $this->uuid,
        ];
    }
}
