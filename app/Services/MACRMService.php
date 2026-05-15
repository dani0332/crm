<?php

namespace App\Services;

use App\Enums\MotorRevivalEnum;
use App\Enums\MotorRevivalVoucherCode;
use App\Enums\QuoteTypes;
use App\Models\CarQuote;
use App\Services\Logger\LoggerService;
use App\Services\Traits\Macrmable;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class MACRMService
{
    use Macrmable;

    private static function sendRequest(string $endpoint, array $data = [], string $method = 'POST')
    {
        try {
            /** @var Response $response */
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

    public static function getAuthToken(): ?string
    {
        $baseUrl = config('constants.MACRM_API_ENDPOINT');
        $apiKey = config('constants.MACRM_API_KEY');
        $apiSecret = config('constants.MACRM_API_SECRET');

        if (blank($apiKey) || blank($apiSecret) || blank($baseUrl)) {
            LoggerService::warning(self::class.'::getAuthToken missing configuration.', [
                'has_macrm_api_endpoint' => filled($baseUrl),
                'has_macrm_api_key' => filled($apiKey),
                'has_macrm_api_secret' => filled($apiSecret),
            ]);

            return null;
        }

        try {
            /** @var Response $response */
            $response = Http::acceptJson()
                ->asJson()
                ->timeout((int) config('constants.LMS_EMAILS_TIMEOUT'))
                ->post(rtrim($baseUrl, '/').'/v1/auth/token', [
                    'api_key' => $apiKey,
                    'api_secret' => $apiSecret,
                ]);
        } catch (Exception $e) {
            LoggerService::warning(self::class.'::getAuthToken HTTP client exception.', [], $e);

            return null;
        }

        if (! $response->successful()) {
            LoggerService::warning(self::class.'::getAuthToken unsuccessful HTTP response.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $token = data_get($response->json(), 'data.token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    public static function createVoucher(array $payload): array
    {
        $token = self::getAuthToken();
        if ($token === null) {
            LoggerService::warning(self::class.'::createVoucher cannot run: auth token missing.', [
                'request_payload' => $payload,
            ]);

            return ['ok' => false, 'reason' => 'auth_token_unavailable'];
        }

        $baseUrl = config('constants.MACRM_API_ENDPOINT');
        if (blank($baseUrl)) {
            LoggerService::warning(self::class.'::createVoucher cannot run: MACRM_API_ENDPOINT not configured.', [
                'request_payload' => $payload,
            ]);

            return ['ok' => false, 'reason' => 'missing_endpoint'];
        }

        $url = rtrim($baseUrl, '/').'/v1/vouchers';

        LoggerService::info(self::class.'::createVoucher request', [
            'url' => $url,
            'request_payload' => $payload,
        ]);

        try {
            /** @var Response $response */
            $response = Http::acceptJson()
                ->asJson()
                ->withToken($token)
                ->timeout((int) config('constants.LMS_EMAILS_TIMEOUT'))
                ->post($url, $payload);
        } catch (Exception $e) {
            LoggerService::warning(self::class.'::createVoucher HTTP client exception (no response).', [
                'url' => $url,
                'request_payload' => $payload,
            ], $e);

            return ['ok' => false, 'reason' => 'http_exception', 'message' => $e->getMessage()];
        }

        $json = $response->json();

        if (! $response->successful()) {
            LoggerService::warning(self::class.'::createVoucher unsuccessful HTTP response.', [
                'url' => $url,
                'http_status' => $response->status(),
                'request_payload' => $payload,
                'response_body_raw' => $response->body(),
                'api_status' => data_get($json, 'status'),
                'api_message' => data_get($json, 'message'),
                'api_validation' => data_get($json, 'data'),
            ]);
        } else {
            LoggerService::info(self::class.'::createVoucher success.', [
                'url' => $url,
                'http_status' => $response->status(),
                'voucher_code' => data_get($payload, 'voucher_code'),
                'api_message' => data_get($json, 'message'),
            ]);
        }

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'json' => $json,
        ];
    }

    public static function voucherCodeExists(string $code): ?bool
    {
        $token = self::getAuthToken();
        if ($token === null) {
            LoggerService::warning(self::class.'::voucherCodeExists auth token unavailable.', [
                'code' => $code,
            ]);

            return null;
        }

        $baseUrl = config('constants.MACRM_API_ENDPOINT');
        if (blank($baseUrl)) {
            LoggerService::warning(self::class.'::voucherCodeExists MACRM_API_ENDPOINT is not configured.');

            return null;
        }

        $url = rtrim($baseUrl, '/').'/v1/vouchers/code/'.rawurlencode($code);

        try {
            /** @var Response $response */
            $response = Http::acceptJson()
                ->withToken($token)
                ->timeout((int) config('constants.LMS_EMAILS_TIMEOUT'))
                ->get($url);
        } catch (Exception $e) {
            LoggerService::warning(self::class.'::voucherCodeExists HTTP client exception.', [
                'code' => $code,
            ], $e);

            return null;
        }

        if ($response->status() === 404) {
            return false;
        }

        if ($response->successful()) {
            $json = $response->json();
            if (data_get($json, 'status') === true && filled(data_get($json, 'data'))) {
                return true;
            }

            return false;
        }

        LoggerService::warning(self::class.'::voucherCodeExists unexpected HTTP response.', [
            'code' => $code,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }

    public static function generateMotorRevivalVoucherForQuote(CarQuote $carQuote): ?string
    {
        $quoteUuid = $carQuote->uuid;
        $voucherCode = MotorRevivalVoucherCode::TrialSevenDay->codeForQuoteUuid($quoteUuid);

        try {
            LoggerService::info(self::class.'::generateMotorRevivalVoucherForQuote start', [
                'quote_uuid' => $quoteUuid,
                'voucher_code' => $voucherCode,
                'car_quote_id' => $carQuote->id,
            ]);

            $exists = self::voucherCodeExists($voucherCode);
            LoggerService::info(self::class.'::generateMotorRevivalVoucherForQuote MACRM voucher existence check', [
                'quote_uuid' => $quoteUuid,
                'voucher_code' => $voucherCode,
                'exists' => $exists,
            ]);

            if ($exists === true) {
                LoggerService::info(self::class.'::generateMotorRevivalVoucherForQuote voucher already in MACRM, skipping create.', [
                    'quote_uuid' => $quoteUuid,
                    'voucher_code' => $voucherCode,
                ]);

                return $voucherCode;
            }

            $validFrom = now()->subDay();
            $validTill = $validFrom->copy()->addDays(7);

            $payload = [
                'voucher_code' => $voucherCode,
                'voucher_type' => 'trial_membership',
                'duration_days' => 7,
                'amount' => 10,
                'valid_from' => $validFrom->format('Y-m-d H:i:s'),
                'valid_till' => $validTill->format('Y-m-d H:i:s'),
                'email' => $carQuote->email,
                'customer_id' => null,
                'max_claims' => 1,
                'promotional_text' => 'Activate Your 7-Day Premium Membership',
                'description' => 'Enjoy exclusive offers, rewards, priority benefits - no policy required.',
                'source' => 'imcrm',
                'is_active' => true,
                'auto_claim' => true,
                'cta_link' => config('constants.AFIA_WEBSITE_DOMAIN')."/set-layout/?header=off&footer=off&redirect=/car-insurance/quote/{$quoteUuid}/",
                'reference_id' => $quoteUuid,
                'reference_type' => strtoupper(QuoteTypes::CAR->value),
                'cta_text' => MotorRevivalEnum::CTA_TEXT->value,
                'max_claims_per_customer' => 1,
            ];

            LoggerService::info(self::class.'::generateMotorRevivalVoucherForQuote createVoucher request', [
                'quote_uuid' => $quoteUuid,
                'request_payload' => $payload,
            ]);

            $result = self::createVoucher($payload);
            if (! ($result['ok'] ?? false)) {
                LoggerService::warning(self::class.'::generateMotorRevivalVoucherForQuote createVoucher did not succeed.', [
                    'quote_uuid' => $quoteUuid,
                    'voucher_code' => $voucherCode,
                    'create_ok' => $result['ok'] ?? null,
                    'http_status' => $result['status'] ?? null,
                    'create_response_json' => $result['json'] ?? null,
                    'request_payload' => $payload,
                ]);

                return null;
            }

            LoggerService::info(self::class.'::generateMotorRevivalVoucherForQuote finished, returning code.', [
                'quote_uuid' => $quoteUuid,
                'voucher_code' => $voucherCode,
            ]);

            return $voucherCode;
        } catch (Exception $e) {
            LoggerService::warning(self::class.'::generateMotorRevivalVoucherForQuote unexpected exception (returning null).', [
                'quote_uuid' => $quoteUuid,
                'voucher_code' => $voucherCode,
            ], $e);

            return null;
        }
    }
}
