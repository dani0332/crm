<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\QuotePolicyBooked;
use App\Services\AlfredCoinsWebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAlfredCoinsInsurancePurchasedWebhook implements ShouldQueue
{
    public int $tries = 3;

    /**
     * Create the event listener.
     */
    public function __construct(
        private readonly AlfredCoinsWebhookService $alfredCoinsWebhookService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(QuotePolicyBooked $event): void
    {
        $this->alfredCoinsWebhookService->sendInsuranceMarketWebhook(
            $event->quoteUID,
            $event->quoteTypeId
        );
    }
}
