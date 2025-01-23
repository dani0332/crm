<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MACRMService
{
    private static function retryLogic(Exception $exception): bool
    {
        info(self::class.'::retryLogic ', [
            'message' => 'API failed',
            'error' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'line' => $exception->getLine(),
        ]);

        $shouldRetry = $exception->getCode() !== 422;

        if ($shouldRetry) {
            info(self::class.'::retryLogic - Going to Retry Request...');
        }

        return $shouldRetry;
    }

    private static function handleResponse(Response $response, string $endpoint): array
    {
        if ($response->status() === 404) {
            $responseBody = json_decode($response->body(), true);
            if (($responseBody['message'] ?? '') === 'Courier not found.') {
                info(self::class." - Specific failure: Courier not found for endpoint {$endpoint}");

                return [
                    'ok' => false,
                    'object' => [
                        'success' => true,
                        'message' => 'Courier not found',
                        'data' => ['status' => 'Pending'],
                    ],
                ];
            }
        }

        if (! $response->successful()) {
            info(self::class." - API request failed with status: {$response->status()} and response: ".$response->body());
        }

        return ['ok' => $response->successful(), 'object' => $response->json()];
    }

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
                ->retry(3, 90000, fn (Exception $exception) => self::retryLogic($exception))
                ->beforeSending(fn () => info(self::class."::sendRequest - Calling MACRM API via {$method} request to {$endpoint}"))
                ->when(
                    $method === 'GET',
                    fn (PendingRequest $http) => $http->get($endpoint, $data),
                    fn (PendingRequest $http) => $http->post($endpoint, $data)
                );

            return self::handleResponse($response, $endpoint);
        } catch (Exception $e) {
            info(self::class." - Exception occurred during API call: {$e->getMessage()}");

            return ['ok' => false, 'object' => null];
        }
    }

    private static function verifySyncPreChecks($lead, $quoteTypeId, $leadData)
    {
        if (! $leadData) {
            info("No lead data found for UUID: {$lead->uuid} and QuoteTypeId: {$quoteTypeId}. Sync aborted.");

            return false;
        }

        if (empty($leadData['payment']['captured_at'])) {
            info("Payment Not captured for courier, for UUID: {$lead->uuid} and QuoteTypeId: {$quoteTypeId}. Sync aborted.");

            return false;
        }

        return true;
    }

    public static function syncCourierQuote($quote, $quoteTypeId)
    {
        try {
            $leadData = getCourierQuote($quote, $quoteTypeId);

            if (! self::verifySyncPreChecks($quote, $quoteTypeId, $leadData)) {
                return false;
            }

            info("Syncing Courier Quote with MACRM for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId}");

            // Send the request using the sendRequest method with 'POST' method
            ['ok' => $ok, 'object' => $response] = self::sendRequest('/couriers/submit-eps', $leadData, 'POST');

            if ($ok) {
                info(self::class." - Synced Courier Quote with MACRM for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId} with message: {$response['message']}");
            } else {
                info(self::class." - Courier Quote Syncing with MACRM Failed for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId} with message: {$response['message']}");
            }

            return $ok;
        } catch (Exception $e) {
            // Log the exception with a detailed message
            info(self::class." - An error occurred while syncing Courier Quote for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId}. Error: {$e->getMessage()}");

            return false;
        }
    }

    public static function cancelCourierQuote($quote, $quoteTypeId)
    {
        try {
            $leadData = getCourierQuote($quote, $quoteTypeId);
            if (! $leadData) {
                info("No lead data found for Courier Quote UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId}");

                return false;
            }

            $leadData = Arr::dot($leadData);
            if (! isset($leadData['payment.ref_id'])) {
                info("No payment reference ID found for Courier Quote UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId}");

                return false;
            }

            $refId = $leadData['payment.ref_id'];

            info("Cancelling Courier Quote on MACRM for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId}");
            ['ok' => $ok, 'object' => $response] = self::sendRequest('/couriers/cancel-courier-status', [
                'ref_id' => $refId,
            ], 'POST');

            if ($ok) {
                info(self::class." - Canceled Courier Quote on MACRM for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId} with message: ".($response['message'] ?? 'No message provided'));
            } else {
                info(self::class." - Courier Quote Canceling on MACRM Failed for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId} with message: ".($response['message'] ?? 'No message provided'));
            }

            return $ok;
        } catch (Exception $e) {
            info(self::class." - Exception occurred while canceling Courier Quote UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId} with error: {$e->getMessage()}");

            return false;
        }
    }

    public static function getCourierQuoteStatus($uuid, $quoteTypeId)
    {
        info(self::class." - Getting Courier Quote Status on MACRM for UUID: {$uuid} and QuoteTypeId: {$quoteTypeId}");

        // making it hard code because not every lead has payment done so we can't get ref_id from payment
        $refId = 'COU-Car-'.$uuid;

        info("Get Courier Quote Status on MACRM for UUID: {$uuid} and QuoteTypeId: {$quoteTypeId}");
        ['ok' => $ok, 'object' => $response] = self::sendRequest("/couriers/get-status/{$refId}", [], 'GET');

        if ($ok) {
            info(self::class." - Get Courier Quote Status on MACRM for UUID: {$uuid} and QuoteTypeId: {$quoteTypeId}.");
        } else {
            info(self::class." - Get Courier Quote Status on MACRM Failed for UUID: {$uuid} and QuoteTypeId: {$quoteTypeId}.");
        }

        return $response;
    }
}
