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

    private int $userId;
    private string $status;
    private int $uploadedCount;
    private string $cdbId;

    public function __construct(int $userId, string $status, int $uploadedCount, string $cdbId)
    {
        $this->userId = $userId;
        $this->status = $status;
        $this->uploadedCount = $uploadedCount;
        $this->cdbId = $cdbId;
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return ['public.'.config('constants.APP_ENV').'.customer.upload'];
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
