<?php

namespace App\Services;

use App\Enums\QuoteStatusEnum;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MACRMService
{
    private static function sendRequest(string $endpoint, array $data)
    {
        try {
            $response = Http::baseUrl(config('constants.MACRM_API_ENDPOINT'))
                ->withBasicAuth(
                    config('constants.MACRM_BASIC_AUTH_USERNAME'),
                    config('constants.MACRM_BASIC_AUTH_PASSWORD'),
                )
                ->withHeader('Referer', trim(config('constants.APP_URL'), '/'))
                ->beforeSending(fn () => info(self::class.' - Calling MACRM API...'))
                ->timeout(config('constants.LMS_EMAILS_TIMEOUT'))
                ->retry(3, 90000, function (Exception $exception) {
                    info(self::class." - API failed with below error: {$exception->getMessage()}");
                    $shouldRetry = $exception->getCode() !== 422;

                    if ($shouldRetry) {
                        info(self::class.' - Going to retry...');
                    }

                    return $shouldRetry;
                })
                ->post($endpoint, $data);

            return [
                'ok' => $response->ok(),
                'object' => $response->object(),
            ];
        } catch (Exception $e) {
            Log::error(self::class." - Error: {$e->getMessage()}");

            return [
                'ok' => false,
                'object' => (object) [
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }

    public static function syncCourierQuote($quote, $quoteTypeId)
    {
        $leadData = getCourierQuote($quote, $quoteTypeId);
        if ($leadData) {
            info("Syncing Courier Quote with MACRM for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId}");
            ['ok' => $ok, 'object' => $response] = self::sendRequest('/couriers/submit-eps', $leadData);

            if ($ok) {
                info(self::class." - Synced Courier Quote with MACRM for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId} with message: {$response->message}");
            } else {
                info(self::class." - Courier Quote Syncing with MACRM Failed for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId} with message: {$response->message}");
            }

            return $ok;
        }

        return false;
    }

    public static function cancelCourierQuote($quote, $quoteTypeId)
    {
        $cancelCriteria = [QuoteStatusEnum::PolicyCancelled];
        if (in_array($quote->quote_status_id, $cancelCriteria)) {
            $leadData = getCourierQuote($quote, $quoteTypeId, $cancelCriteria);
            if ($leadData) {
                $leadData = Arr::dot($leadData);
                if (isset($leadData['payment.ref_id'])) {
                    $refId = $leadData['payment.ref_id'];

                    info("Cancelling Courier Quote on MACRM for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId}");
                    ['ok' => $ok, 'object' => $response] = self::sendRequest('/couriers/cancel-courier-status', [
                        'ref_id' => $refId,
                    ]);

                    if ($ok) {
                        info(self::class." - Canceled Courier Quote on MACRM for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId} with message: {$response->message}");
                    } else {
                        info(self::class." - Courier Quote Canceling on MACRM Failed for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId} with message: {$response->message}");
                    }

                    return $ok;
                }
            }
        } else {
            info("Cannot cancel Courier Quote on MACRM for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId} as quote status is not 'Policy Cancelled'");
        }

        return false;
    }

    public static function pendingCourierQuote($quote, $quoteTypeId)
    {
        $leadData = getCourierQuote($quote, $quoteTypeId, [QuoteStatusEnum::PolicyIssued]);

        if ($leadData) {
            $leadData = Arr::dot($leadData);
            if (isset($leadData['payment.ref_id'])) {
                $refId = $leadData['payment.ref_id'];

                info("Pending Courier Quote on MACRM for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId}");
                ['ok' => $ok, 'object' => $response] = self::sendRequest('/couriers/cancel-courier-status', [
                    'ref_id' => $refId,
                ]);

                if ($ok) {
                    info(self::class.'Respone: '.json_encode($response));
                    info(self::class.'Ok'.json_encode($ok));
                    info(self::class." - Pending Courier Quote on MACRM for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId} with message: {$response->message}");
                } else {
                    info(self::class." - Courier Quote Pending on MACRM Failed for UUID: {$quote->uuid} and QuoteTypeId: {$quoteTypeId} with message: {$response->message}");
                }

                return $ok;
            }
        }

        return false;
    }
}
