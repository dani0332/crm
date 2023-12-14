<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentNotifications implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public $status;
    public $userName;
    public $message;

    public function __construct($userId)
    {
        $this->userId = $userId;
        $this->status = 1;
        $this->userName = 'Mirza SB';

        /*UserStatusAuditLog::create([
            'user_id' => $userId,
            'status' => $status,
            'status_changed_at' => now()->toDateTimeString(),
        ]);*/

        $this->message = ' Payment successfully done.';
    }

    public function broadcastOn()
    {
        return ['public.'.config('constants.APP_ENV').'.activity.user'];
    }

    public function broadcastAs()
    {
        return 'payment.notification';
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
}
