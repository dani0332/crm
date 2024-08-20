<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentNotifications implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private string $uuid;
    private string $clientName;
    private int $advisorId;
    private string $message;
    private string $url;
    private string $quoteType;

    public function __construct($model, $url, $quoteType)
    {
        $this->uuid = $model->uuid;
        $this->clientName = "$model->first_name $model->last_name";
        $this->advisorId = $model->advisor_id;
        $this->message = "$this->clientName has authorized the payment for ";
        $this->url = $url;
        $this->quoteType = $quoteType;
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
        info('Notification Send', [$this->uuid]);

        return [
            'uuid' => $this->uuid,
            'advisorId' => $this->advisorId,
            'message' => $this->message,
            'url' => $this->url,
            'quoteType' => $this->quoteType,
        ];
    }
}
