<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\QuotePolicyBooked;
use App\Services\ConversionApiService;
use App\Services\Logger\LoggerService;
use Exception;

class TriggerConversionApis
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private readonly ConversionApiService $conversionApiService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(QuotePolicyBooked $event): void
    {
        try {
            LoggerService::info('TriggerConversionApis - Processing conversion APIs for PolicyBooked quote', [
                'quoteUID' => $event->quoteUID,
                'quoteTypeId' => $event->quoteTypeId,
                'eventType' => $event->eventType,
            ]);

            // Trigger Facebook conversion API
            $facebookSuccess = $this->conversionApiService->triggerFacebookConversion(
                $event->quoteUID,
                $event->quoteTypeId,
                $event->eventType
            );

            // Trigger Google conversion API
            $googleSuccess = $this->conversionApiService->triggerGoogleConversion(
                $event->quoteUID,
                $event->quoteTypeId,
                $event->eventType
            );

            LoggerService::info('TriggerConversionApis - Conversion APIs processing completed', [
                'quoteUID' => $event->quoteUID,
                'quoteTypeId' => $event->quoteTypeId,
                'eventType' => $event->eventType,
                'facebookSuccess' => $facebookSuccess,
                'googleSuccess' => $googleSuccess,
            ]);
        } catch (Exception $e) {
            LoggerService::error('TriggerConversionApis - Exception occurred while processing conversion APIs', [
                'quoteUID' => $event->quoteUID,
                'quoteTypeId' => $event->quoteTypeId,
                'eventType' => $event->eventType,
                'error' => $e->getMessage(),
            ], exception: $e);
        }
    }
}
