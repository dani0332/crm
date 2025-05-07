<?php

namespace App\Listeners;

use App\Events\PrivateClientUpdatedEvent;
use App\Traits\PrivateClient;

class ApplyPrivateClientTagListener
{
    use PrivateClient;

    /**
     * Handle the event.
     */
    public function handle(PrivateClientUpdatedEvent $event): void
    {
        $this->applyPcpTag($event->leadId, $event->quoteTypeId);
    }
}
