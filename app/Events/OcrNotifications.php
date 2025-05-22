<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OcrNotifications implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $uuid;
    public int $userId;
    public string $status; // start, end, fail
    public string $message;
    public ?string $error;

    public function __construct($quote, string $status, string $message, ?string $error = null)
    {
        $this->uuid = $quote->uuid;
        $this->userId = $quote->advisor_id ?? ($quote->created_by_id ?? 0);
        $this->status = $status;
        $this->message = $message;
        $this->error = $error;
    }

    public function broadcastOn()
    {
        return ['public.'.config('constants.APP_ENV').'.ocr.user'];
    }

    public function broadcastAs()
    {
        return 'ocr.notification';
    }

    public function broadcastWith()
    {
        return [
            'uuid' => $this->uuid,
            'userId' => $this->userId,
            'status' => $this->status,
            'message' => $this->message,
            'error' => $this->error,
        ];
    }
}
