<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerUploadCompleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private readonly int $userId,
        private readonly string $status,
        private readonly int $uploadedCount,
        private readonly string $cdbId,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('public.'.config('constants.APP_ENV').'.customer.upload.'.$this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'customer.upload.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'userId' => $this->userId,
            'status' => $this->status,
            'uploadedCount' => $this->uploadedCount,
            'cdbId' => $this->cdbId,
        ];
    }
}
