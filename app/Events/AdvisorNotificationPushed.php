<?php

declare(strict_types=1);

namespace App\Events;

use App\Services\Logger\LoggerService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdvisorNotificationPushed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private array $pusherData;
    private int $advisorId;

    /**
     * Create a new event instance.
     */
    public function __construct(array $pusherData, int $advisorId)
    {
        $this->pusherData = $pusherData;
        $this->advisorId = $advisorId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, string>
     */
    public function broadcastOn(): array
    {
        return ['public.'.config('constants.APP_ENV').'.activity.user'];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'stp.advisor.notification';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $data = array_merge($this->pusherData, [
            'advisorId' => $this->advisorId,
        ]);

        LoggerService::info('AdvisorNotificationPushed broadcasting data:', $data);

        return $data;
    }
}
