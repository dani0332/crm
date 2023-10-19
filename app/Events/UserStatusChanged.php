<?php

namespace App\Events;

use App\Models\UserStatusAuditLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $status;
    public $userName;
    public $message;

    public function __construct($userId, $status, $userName)
    {
        $this->userId = $userId;
        $this->status = $status;
        $this->userName = $userName;

        UserStatusAuditLog::create([
            'user_id' => $userId,
            'status' => $status,
            'status_changed_at' => now()->toDateTimeString(),
        ]);

        $this->message = ' Status Changed to '.$this->getStatusText($status);
    }

    public function broadcastOn()
    {
        return ['public.'.config('constants.APP_ENV').'.activity.user'];
    }

    public function broadcastAs()
    {
        return 'user.status.changed';
    }

    public function broadcastWith()
    {
        return [
            'userId' => $this->userId,
            'status' => $this->status,
            'message' => $this->message,
            'userName' => $this->userName,
        ];
    }

    public function getStatusText($status)
    {
        $statusText = '';
        switch ($status) {
            case 1:
                $statusText = 'Available';
                break;
            case 2:
                $statusText = 'Offline';
                break;
            case 3:
                $statusText = 'Unavailable';
                break;
            case 4:
                $statusText = 'Sick';
                break;
            case 5:
                $statusText = 'On Leave';
                break;
            default:
                break;
        }

        return $statusText;
    }
}
