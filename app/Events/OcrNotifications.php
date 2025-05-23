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
    public string $docType;

    public function __construct($quote, string $status, string $message, ?string $error = null, ?string $docType = null, int $userId = 0)
    {
        $this->uuid = $quote->uuid;
        $this->userId = $userId;
        $this->status = $status;
        $this->message = $message;
        $this->error = $error;
        $this->docType = $docType;
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
            'docType' => $this->docType,
        ];
    }
}
