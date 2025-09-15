<?php

namespace App\Listeners;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTypes;
use App\Events\PrivateClientUpdatedEvent;
use App\Services\Logger\LoggerService;
use App\Traits\PrivateClient;

class ApplyPrivateClientTagListener
{
    use PrivateClient;

    /**
     * Handle the event.
     */
    public function handle(PrivateClientUpdatedEvent $event): void
    {
        LoggerService::startQuoteLogging(QuoteTypes::getName($event->quoteTypeId)->refId($event->lead->uuid), LoggerFeatureEnum::PCP_CLIENT);

        LoggerService::info('private client tag event has been triggered.');

        $this->applyPcpTag($event->lead->uuid, $event->quoteTypeId);

        LoggerService::info('private client tag event has been ended.');

        LoggerService::endLogging();
    }
}
