<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentExpireNotifications implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private string $uuid;
    private int $advisorId;
    private string $message;
    private string $url;
    private string $quoteUuid;

    public function __construct($lead, $url, $quoteUuid)
    {
        $this->uuid = $lead->uuid;
        $this->advisorId = $lead->advisor_id;
        $this->message = "The payment for $this->uuid will expire in 2 days";
        $this->url = $url;
        $this->quoteUuid = $quoteUuid;
    }

    public function broadcastOn()
    {
        return ['public.'.config('constants.APP_ENV').'.activity.user'];
    }

    public function broadcastAs()
    {
        return 'expire.notification';
    }

    public function broadcastWith()
    {
        info('Payment Expire Notification sent to Advisor: '.$this->advisorId.' and Quote ID: '.$this->quoteUuid);

        return [
            'uuid' => $this->uuid,
            'advisorId' => $this->advisorId,
            'message' => $this->message,
            'url' => $this->url,
            'quoteUuid' => $this->quoteUuid,
        ];
    }
}
