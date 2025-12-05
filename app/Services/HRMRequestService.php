<?php

namespace App\Services;

use App\Enums\CacheKeyEnum;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class HRMRequestService
{
    private ?string $accessToken;

    public function __construct(?string $accessToken = null)
    {
        $this->accessToken = $accessToken ?? Cache::get(CacheKeyEnum::HRM_API_ACCESS_TOKEN->value);
    }

    /**
     * Get access token for HRM API
     */
    private function getAccessToken(bool $forceRefresh = false): string|false
    {
        if ($forceRefresh) {
            $this->invalidateAccessToken();
        } elseif ($this->accessToken) {
            return $this->accessToken;
        } else {
            $cachedToken = Cache::get(CacheKeyEnum::HRM_API_ACCESS_TOKEN->value);
            if ($cachedToken) {
                $this->accessToken = $cachedToken;

                return $cachedToken;
            }
        }

        $clientId = config('constants.HRM_API_CLIENT_ID');
        $clientSecret = config('constants.HRM_API_CLIENT_SECRET');
        $apiEndPoint = config('constants.HRM_API_ENDPOINT');

        if (empty($clientId) || empty($clientSecret) || empty($apiEndPoint)) {
            LoggerService::error(static::class.'::getAccessToken - Missing API configuration');

            return false;
        }

        $response = $this->sendRequest('/v1/auth/obtain-token', [
            'api_key' => $clientId,
            'api_secret' => $clientSecret,
        ], 'POST', false);

        if ($response !== false && isset($response['data']['token'])) {
            $token = $response['data']['token'];

            $this->cacheAccessToken($token);

            return $token;
        }

        LoggerService::error(static::class.'::getAccessToken - Failed to obtain token', [
            'response' => $response,
        ]);

        return false;
    }

    private function cacheAccessToken(string $token): void
    {
        $this->accessToken = $token;
        Cache::put(
            CacheKeyEnum::HRM_API_ACCESS_TOKEN->value,
            $token,
            CacheKeyEnum::HRM_API_ACCESS_TOKEN->expiry()
        );
    }

    private function invalidateAccessToken(): void
    {
        $this->accessToken = null;
        Cache::forget(CacheKeyEnum::HRM_API_ACCESS_TOKEN->value);
    }

    /**
     * Send HTTP request to HRM API
     *
     * @param  string  $endpointPath  The API endpoint path (e.g., '/v1/employees/codes')
     * @param  array  $payload  The request payload
     * @param  string  $method  HTTP method (default: 'POST')
     * @param  bool  $requiresAuth  Whether the request needs an access token
     * @param  bool  $retryOnAuthFailure  Whether to retry once when auth fails
     * @return array|false Returns decoded response on success, false on failure
     */
    private function sendRequest(
        string $endpointPath,
        array $payload = [],
        string $method = 'POST',
        bool $requiresAuth = true,
        bool $retryOnAuthFailure = true
    ): array|false {
        try {
            $apiEndPoint = config('constants.HRM_API_ENDPOINT');
            $apiTimeout = config('constants.HRM_API_TIMEOUT', 2);

            if (empty($apiEndPoint)) {
                LoggerService::error(static::class.'::sendRequest - Missing API configuration', [
                    'endpoint_path' => $endpointPath,
                ]);

                return false;
            }

            $accessToken = null;
            if ($requiresAuth) {
                $accessToken = $this->getAccessToken();
                if (! $accessToken) {
                    LoggerService::error(static::class.'::sendRequest - Unable to get access token', [
                        'endpoint_path' => $endpointPath,
                    ]);

                    return false;
                }

                $this->accessToken = $accessToken;
            }

            $fullUrl = rtrim($apiEndPoint, '/').'/'.ltrim($endpointPath, '/');

            LoggerService::info(static::class.'::sendRequest - Making API request', [
                'method' => $method,
                'url' => $fullUrl,
                'payload_keys' => array_keys($payload),
                'timeout' => $apiTimeout,
                'requires_auth' => $requiresAuth,
            ]);

            $httpResponse = Http::timeout($apiTimeout)
                ->acceptJson()
                ->asJson();

            if ($requiresAuth && $accessToken) {
                $httpResponse = $httpResponse->withToken($accessToken);
            }

            $response = match (strtoupper($method)) {
                'GET' => $httpResponse->get($fullUrl, $payload),
                'POST' => $httpResponse->post($fullUrl, $payload),
                'PUT' => $httpResponse->put($fullUrl, $payload),
                'DELETE' => $httpResponse->delete($fullUrl, $payload),
                default => throw new Exception("Unsupported HTTP method: {$method}")
            };

            $statusCode = $response->status();
            $contentType = strtolower((string) ($response->header('Content-Type') ?? ''));
            $responseData = $response->json();
            $responseArray = is_array($responseData) ? $responseData : [];

            //region Retrying Block
            $shouldRetry = $requiresAuth
                && $retryOnAuthFailure
                && (
                    str_contains($contentType, 'text/html')
                    || in_array($statusCode, [401, 422])
                );

            if ($shouldRetry) {
                LoggerService::warning(static::class.'::sendRequest - Auth failure detected, refreshing token', [
                    'endpoint_path' => $endpointPath,
                    'status_code' => $statusCode,
                    'content_type' => $contentType,
                    'response_message' => $responseArray['message'] ?? null,
                    'retry_reason' => in_array($statusCode, [401, 422]) ? 'error' : 'html_or_message_indicator',
                ]);

                $this->invalidateAccessToken();
                $refreshedToken = $this->getAccessToken(forceRefresh: true);

                if ($refreshedToken) {
                    return $this->sendRequest($endpointPath, $payload, $method, $requiresAuth, false);
                }

                return false;
            }
            //endregion

            LoggerService::info(static::class.'::sendRequest - API response received', [
                'status_code' => $statusCode,
                'response_status' => $responseArray['status'] ?? null,
                'message' => $responseArray['message'] ?? null,
                'data_count' => isset($responseArray['data']) ? count($responseArray['data']) : 0,
            ]);

            if ($response->successful()) {
                if (isset($responseArray['status']) && $responseArray['status'] === true) {
                    LoggerService::info(static::class.'::sendRequest - API request successful');

                    return $responseArray;
                } else {
                    LoggerService::warning(static::class.'::sendRequest - API returned unsuccessful status', [
                        'response' => $responseData,
                    ]);

                    return false;
                }
            } else {
                LoggerService::warning(static::class.'::sendRequest - API returned error status code', [
                    'status_code' => $statusCode,
                    'response' => $responseData,
                ]);

                return false;
            }

        } catch (Exception $exception) {
            LoggerService::error(static::class.'::sendRequest - Exception occurred', [
                'endpoint_path' => $endpointPath,
                'payload' => $payload,
            ], exception: $exception);

            return false;
        }
    }

    /**
     * Get employee codes by email addresses
     *
     * @param  array  $emails  Array of email addresses
     * @return array|false Returns employee data array on success, false on failure
     */
    public function getEmployeeCodes(array $emails): array|false
    {
        LoggerService::info(static::class.'::getEmployeeCodes - Starting employee codes request', [
            'emails_count' => count($emails),
            'emails' => $emails,
        ]);

        $requestPayload = [
            'emails' => $emails,
        ];

        $apiResponse = $this->sendRequest('/v1/employees/codes', $requestPayload, 'POST');

        if ($apiResponse !== false) {
            LoggerService::info(static::class.'::getEmployeeCodes - Successfully retrieved employee codes');

            return $apiResponse['data'] ?? [];
        }

        LoggerService::error(static::class.'::getEmployeeCodes - Failed to retrieve employee codes');

        return false;
    }
}
