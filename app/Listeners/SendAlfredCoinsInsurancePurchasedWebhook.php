<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\QuotePolicyBooked;
use App\Services\AlfredCoinsWebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAlfredCoinsInsurancePurchasedWebhook implements ShouldQueue
{
    public function handle(QuotePolicyBooked $event): void
    {
        app(AlfredCoinsWebhookService::class)->sendInsuranceMarketWebhook(
            $event->quoteUID,
            $event->quoteTypeId
        );
    }
}
