<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InstantAlfredWhatsappReminderNotification implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private string $uuid;
    private int $advisorId;
    private string $url;
    private string $quoteUuid;

    public function __construct($uuid, $advisor_id, $url, $quoteUuid)
    {
        $this->uuid = $uuid;
        $this->advisorId = $advisor_id;
        $this->url = $url;
        $this->quoteUuid = $quoteUuid;
    }

    public function broadcastOn()
    {
        return ['public.'.config('constants.APP_ENV').'.activity.user'];
    }

    public function broadcastAs()
    {
        return 'whatsapp.reminder.notification';
    }

    public function broadcastWith()
    {
        info("InstantAlfred Whatsapp Reminder Notification Event Trigger to Advisor {$this->advisorId} and Code is {$this->uuid}");

        return [
            'uuid' => $this->uuid,
            'advisorId' => $this->advisorId,
            'url' => $this->url,
            'quoteUuid' => $this->quoteUuid,
        ];
    }
}
