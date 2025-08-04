<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentNotificationEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $data;
    public string $status;

    public function __construct(array $data, string $status = 'info')
    {
        $this->data = $data;
        $this->status = $status;
    }

    public function broadcastOn()
    {
        return ['public.'.config('constants.APP_ENV').'.document.notification'];
    }

    public function broadcastAs()
    {
        return 'document.notification';
    }

    public function broadcastWith()
    {
        return [
            'data' => $this->data,
            'status' => $this->status,
        ];
    }
} 