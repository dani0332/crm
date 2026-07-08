<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\LeadSourceEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Events\QuotePolicyBooked;
use App\Services\ConversionApiService;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;

class TriggerConversionApis implements ShouldQueue
{
    public $tries = 3;

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
            LoggerService::startQuoteLogging($event->quoteUID, LoggerFeatureEnum::CONVERSION_API);
            LoggerService::info('TriggerConversionApis - Processing conversion APIs for PolicyBooked quote', [], [
                'quoteTypeId' => $event->quoteTypeId,
                'eventType' => $event->eventType,
            ]);

            // Trigger Facebook conversion API
            $facebookSuccess = $this->conversionApiService->triggerFacebookConversion(
                $event->quoteUID,
                $event->quoteTypeId,
                $event->eventType
            );

            // Trigger Google conversion API, unless the lead came from a renewal upload
            $googleSuccess = false;
            if ($event->leadSource !== LeadSourceEnum::RENEWAL_UPLOAD) {
                $googleSuccess = $this->conversionApiService->triggerGoogleConversion(
                    $event->quoteUID,
                    $event->quoteTypeId,
                    $event->eventType
                );
            } else {
                LoggerService::info('TriggerConversionApis - Skipping Google conversion API for renewal upload lead source', [], [
                    'quoteTypeId' => $event->quoteTypeId,
                    'eventType' => $event->eventType,
                    'leadSource' => $event->leadSource,
                ]);
            }

            LoggerService::info('TriggerConversionApis - Conversion APIs processing completed', [], [
                'quoteTypeId' => $event->quoteTypeId,
                'eventType' => $event->eventType,
                'leadSource' => $event->leadSource,
                'facebookSuccess' => $facebookSuccess,
                'googleSuccess' => $googleSuccess,
            ]);
        } catch (Exception $e) {
            LoggerService::error('TriggerConversionApis - Exception occurred while processing conversion APIs', [], $e, [
                'quoteTypeId' => $event->quoteTypeId,
                'eventType' => $event->eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
