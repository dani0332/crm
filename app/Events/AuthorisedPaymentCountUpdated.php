<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuthorisedPaymentCountUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private int $userId;
    private int $count;

    /**
     * Create a new event instance.
     */
    public function __construct(int $userId, int $count)
    {
        $this->userId = $userId;
        $this->count = $count;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return ['public.'.config('constants.APP_ENV').'.authorised-payment-count'];
    }

    public function broadcastAs(): string
    {
        return 'authorised.payment.count.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'userId' => $this->userId,
            'count' => $this->count,
        ];
    }
}
