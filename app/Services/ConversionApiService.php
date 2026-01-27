<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Facades\Capi;
use App\Services\Logger\LoggerService;
use Exception;

class ConversionApiService
{
    private const FACEBOOK_ENDPOINT = '/api/v1-trigger-facebook-event-conversion';
    private const GOOGLE_ENDPOINT = '/api/v1-trigger-google-event-conversion';
    private const EVENT_TYPE_PURCHASE = 'Purchase';

    /**
     * Trigger Facebook conversion API
     *
     * @param  string  $quoteUID  Quote UUID
     * @param  int  $quoteTypeId  Quote Type ID
     * @param  string  $eventType  Event type (default: Purchase)
     * @return bool Success status
     */
    public function triggerFacebookConversion(string $quoteUID, int $quoteTypeId, string $eventType = self::EVENT_TYPE_PURCHASE): bool
    {
        return $this->sendConversionRequest(self::FACEBOOK_ENDPOINT, $quoteUID, $quoteTypeId, $eventType, 'facebook');
    }

    /**
     * Trigger Google conversion API
     *
     * @param  string  $quoteUID  Quote UUID
     * @param  int  $quoteTypeId  Quote Type ID
     * @param  string  $eventType  Event type (default: Purchase)
     * @return bool Success status
     */
    public function triggerGoogleConversion(string $quoteUID, int $quoteTypeId, string $eventType = self::EVENT_TYPE_PURCHASE): bool
    {
        return $this->sendConversionRequest(self::GOOGLE_ENDPOINT, $quoteUID, $quoteTypeId, $eventType, 'google');
    }

    /**
     * Send conversion API request
     *
     * @param  string  $endpoint  API endpoint
     * @param  string  $quoteUID  Quote UUID
     * @param  int  $quoteTypeId  Quote Type ID
     * @param  string  $eventType  Event type
     * @param  string  $platform  Platform name (facebook/google)
     * @return bool Success status
     */
    private function sendConversionRequest(string $endpoint, string $quoteUID, int $quoteTypeId, string $eventType, string $platform): bool
    {
        $payload = [
            'quoteUID' => $quoteUID,
            'quoteTypeId' => $quoteTypeId,
            'eventType' => $eventType,
        ];

        LoggerService::startFeatureLogging(LoggerFeatureEnum::CONVERSION_API);
        LoggerService::startQuoteLogging($quoteUID, LoggerFeatureEnum::CONVERSION_API);

        LoggerService::info("ConversionApiService - Calling {$platform} conversion API", [], [
            'uuid' => $quoteUID,
            'eventType' => $eventType,
            'platform' => $platform,
            'quoteTypeId' => $quoteTypeId,
            'endpoint' => $endpoint,
            'payload' => $payload,
        ]);

        try {
            $response = Capi::request($endpoint, 'post', $payload);

            // Validate response - Capi::request does not throw exceptions for API failures
            if (isset($response->errors)) {
                LoggerService::error("ConversionApiService - {$platform} conversion API returned errors", [], null, [
                    'uuid' => $quoteUID,
                    'eventType' => $eventType,
                    'platform' => $platform,
                    'quoteTypeId' => $quoteTypeId,
                    'endpoint' => $endpoint,
                    'response' => $response,
                    'errors' => $response->errors,
                ]);

                return false;
            }

            // Validate that response exists and is not empty
            if (! $response) {
                LoggerService::error("ConversionApiService - {$platform} conversion API returned empty response", [], null, [
                    'uuid' => $quoteUID,
                    'eventType' => $eventType,
                    'platform' => $platform,
                    'quoteTypeId' => $quoteTypeId,
                    'endpoint' => $endpoint,
                ]);

                return false;
            }

            LoggerService::info("ConversionApiService - {$platform} conversion API call successful", [], [
                'uuid' => $quoteUID,
                'eventType' => $eventType,
                'platform' => $platform,
                'quoteTypeId' => $quoteTypeId,
                'endpoint' => $endpoint,
                'response' => $response,
            ]);

            return true;
        } catch (Exception $e) {
            LoggerService::error("ConversionApiService - {$platform} conversion API call exception", [], $e, [
                'uuid' => $quoteUID,
                'eventType' => $eventType,
                'platform' => $platform,
                'quoteTypeId' => $quoteTypeId,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return false;
        } finally {
            LoggerService::endLogging();
        }
    }
}
