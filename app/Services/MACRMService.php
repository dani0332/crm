<?php

namespace App\Services;

use App\Enums\QuoteTypes;
use App\Services\Logger\LoggerService;
use App\Services\Traits\Macrmable;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class MACRMService
{
    use Macrmable;

    private static function sendRequest(string $endpoint, array $data = [], string $method = 'POST')
    {
        try {
            $response = Http::baseUrl(config('constants.MACRM_API_ENDPOINT'))
                ->withBasicAuth(
                    config('constants.MACRM_BASIC_AUTH_USERNAME'),
                    config('constants.MACRM_BASIC_AUTH_PASSWORD')
                )
                ->withHeader('Referer', trim(config('constants.APP_URL'), '/'))
                ->timeout(config('constants.LMS_EMAILS_TIMEOUT'))
                ->beforeSending(fn () => LoggerService::info(self::class."::sendRequest - Calling MACRM API via {$method} request to {$endpoint}"))
                ->when(
                    $method === 'GET',
                    fn (PendingRequest $http) => $http->get($endpoint, $data),
                    fn (PendingRequest $http) => $http->post($endpoint, $data)
                );

            return self::handleResponse($response, $endpoint);
        } catch (Exception $e) {
            LoggerService::error(self::class." - Exception occurred during API call: {$e->getMessage()}", [
                'endpoint' => $endpoint,
                'trace' => $e->getTraceAsString(),
            ]);

            return ['ok' => false, 'object' => null, 'message' => $e->getMessage()];
        }
    }

    public static function syncCourierQuote($quote, $quoteTypeId)
    {
        LoggerService::info(self::class." - Inside syncCourierQuote method for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId}");
        $leadData = getCourierQuote($quote, $quoteTypeId);

        try {
            if (! self::verifySyncPreChecks($quote, $quoteTypeId, $leadData)) {
                LoggerService::warning(self::class." - Pre-checks failed for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId}");
                return false;
            }

            LoggerService::info("Syncing Courier Quote with MACRM for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId}");

            ['ok' => $ok, 'object' => $response, 'message' => $message] = self::sendRequest('/couriers/submit-eps', $leadData);

            $refId = self::getRefId($leadData);
            self::saveSyncResponse($refId, $ok, $response, $message);

            if ($ok) {
                LoggerService::info(self::class." - Synced Courier Quote with MACRM for UUID: {$quote->uuid}, RefId: {$refId} and QuoteTypeId: {$quoteTypeId} with message: {$message}");
            } else {
                LoggerService::warning(self::class." - Courier Quote Syncing with MACRM Failed for UUID: {$quote->uuid}, RefId: {$refId} and QuoteTypeId: {$quoteTypeId} with message: {$message}");
            }

            return $ok;
        } catch (Exception $e) {
            self::endSyncProcessing(self::getRefId($leadData));
            // Log the exception with a detailed message
            LoggerService::error(self::class." - An error occurred while syncing Courier Quote for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId}. Error: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    public static function cancelCourierQuote($quote, $quoteTypeId)
    {
        try {
            $leadData = getCourierQuote($quote, $quoteTypeId);
            if (! $leadData) {
                LoggerService::warning("No lead data found for Courier Quote UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId}");

                return false;
            }

            $leadData = Arr::dot($leadData);
            if (! isset($leadData['payment.ref_id'])) {
                LoggerService::warning("No payment reference ID found for Courier Quote UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId}");

                return false;
            }

            $refId = $leadData['payment.ref_id'];

            LoggerService::info("Cancelling Courier Quote on MACRM for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId}");
            ['ok' => $ok, 'object' => $response] = self::sendRequest('/couriers/cancel-courier-status', [
                'ref_id' => $refId,
            ]);

            if ($ok) {
                LoggerService::info(self::class." - Canceled Courier Quote on MACRM for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId} with message: ".($response['message'] ?? 'No message provided'));
            } else {
                LoggerService::warning(self::class." - Courier Quote Canceling on MACRM Failed for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId} with message: ".($response['message'] ?? 'No message provided'));
            }

            return $ok;
        } catch (Exception $e) {
            LoggerService::error(self::class." - Exception occurred while canceling Courier Quote UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId} with error: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    public static function getCourierQuoteStatus($uuid, $quoteTypeId)
    {
        $quoteType = QuoteTypes::getName($quoteTypeId);
        $model = $quoteType?->model();
        $quote = $model::where('uuid', $uuid)->first();

        $leadData = getCourierQuote($quote, $quoteTypeId);
        if (! $leadData) {
            return false;
        }

        LoggerService::info(self::class." - Getting Courier Quote Status on MACRM for UUID: {$uuid} and QuoteTypeId: {$quoteTypeId}");

        // making it hard code because not every lead has payment done so we can't get ref_id from payment
        $refId = "COU-{$quote->code}";

        LoggerService::info("Get Courier Quote Status on MACRM for UUID: {$uuid} and QuoteTypeId: {$quoteTypeId}");
        ['ok' => $ok, 'object' => $response] = self::sendRequest("/couriers/get-status/{$refId}", [], 'GET');

        if ($ok) {
            LoggerService::info(self::class." - Get Courier Quote Status on MACRM for UUID: {$uuid} and QuoteTypeId: {$quoteTypeId}.");
        } else {
            LoggerService::warning(self::class." - Get Courier Quote Status on MACRM Failed for UUID: {$uuid} and QuoteTypeId: {$quoteTypeId}.");
        }

        return $response;
    }
}
