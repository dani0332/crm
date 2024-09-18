<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallBackNotifications implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private string $uuid;
    private int $advisorId;
    private string $url;
    private string $quoteUuid;
    private string $title;
    private string $message;

    public function __construct($uuid, $advisor_id, $url, $quoteUuid, $title, $message)
    {
        $this->uuid = $uuid;
        $this->advisorId = $advisor_id;
        $this->url = $url;
        $this->quoteUuid = $quoteUuid;
        $this->title = $title;
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return ['public.'.config('constants.APP_ENV').'.activity.user'];
    }

    public function broadcastAs()
    {
        return 'callback.notification';
    }

    public function broadcastWith()
    {
        info("InstantAlfred Callback Notification Send to {$this->advisorId} and Quote Code is {$this->quoteUuid}");

        return [
            'uuid' => $this->uuid,
            'advisorId' => $this->advisorId,
            'url' => $this->url,
            'quoteUuid' => $this->quoteUuid,
            'title' => $this->title,
            'message' => $this->message,
        ];
    }
}
