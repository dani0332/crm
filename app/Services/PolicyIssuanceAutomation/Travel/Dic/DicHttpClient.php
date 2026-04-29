<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Travel\Dic;

use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class DicHttpClient
{
    /** Laravel Redis cache key — value set by {@see self::fetchAccessTokenAndCache}. */
    public const REDIS_TOKEN_KEY = 'dic-token';

    private int $apiTimeout;

    public function __construct()
    {
        $this->apiTimeout = (int) config('constants.DIC_API_TIMEOUT', 90);
    }

    public function buildUrl(string $path): string
    {
        $base = rtrim((string) config('constants.DIC_API_BASE_URL', ''), '/');
        $path = ltrim($path, '/');

        return $base !== '' && $path !== '' ? $base.'/'.$path : '';
    }

    /**
     * Configured API base URL without trailing slash (host + embed path prefix).
     */
    public function getBaseUrl(): string
    {
        return rtrim((string) config('constants.DIC_API_BASE_URL', ''), '/');
    }

    /**
     * Bearer-authenticated request. Returns null when no token is available (before or after 401 retry).
     *
     * @param  array<string, mixed>  $data  Query string (GET) or JSON body (POST)
     */
    public function authenticatedRequest(string $method, string $fullUrl, array $data = []): ?Response
    {
        $token = $this->getBearerToken();
        if ($token === null) {
            return null;
        }

        LoggerService::info('Initiating DIC API call', [
            'method' => strtoupper($method),
            'url' => $fullUrl,
            'timeout' => $this->apiTimeout,
        ]);

        try {
            $response = $this->sendWithBearer($method, $fullUrl, $token, $data);

            if ($response->status() === 401) {
                Cache::store('redis')->forget(self::REDIS_TOKEN_KEY);
                $token = $this->getBearerToken();
                if ($token === null) {
                    return null;
                }
                $response = $this->sendWithBearer($method, $fullUrl, $token, $data);
            }

            if ($response->failed()) {
                $mapped = DicEnsuredItErrorHandler::map($response);
                LoggerService::error('DIC API HTTP error response', [
                    'method' => strtoupper($method),
                    'status_code' => $response->status(),
                    'ensuredit_operator_message' => $mapped['message'],
                    'ensuredit_error_detail' => $mapped['error'],
                    'response_body' => $response->body(),
                ]);
            } else {
                LoggerService::info('DIC API call completed', [
                    'method' => strtoupper($method),
                    'status_code' => $response->status(),
                ]);
            }

            return $response;
        } catch (Exception $ex) {
            LoggerService::error('DIC API call exception', [
                'method' => strtoupper($method),
                'url' => $fullUrl,
            ], exception: $ex);

            throw $ex;
        }
    }

    private function getBearerToken(): ?string
    {
        $value = Cache::store('redis')->get(self::REDIS_TOKEN_KEY);
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return $this->fetchAccessTokenAndCache();
    }

    /**
     * POST auth/generate (EnsuredIT) and store {@see self::REDIS_TOKEN_KEY} until shortly before `expiresIn`.
     */
    private function fetchAccessTokenAndCache(): ?string
    {
        $username = (string) config('constants.DIC_API_USERNAME', '');
        $password = (string) config('constants.DIC_API_PASSWORD', '');
        $url = $this->buildUrl('auth/generate');

        $token = null;
        $response = null;

        try {
            $response = Http::timeout($this->apiTimeout)
                ->acceptJson()
                ->asJson()
                ->post($url, [
                    'username' => $username,
                    'password' => $password,
                ]);
        } catch (Exception $ex) {
            LoggerService::error('DIC API token request exception', [], $ex);
        }

        if ($response !== null && $response->successful()) {
            $payload = $response->json();
            $accessToken = $payload['accessToken'] ?? null;
            if (is_string($accessToken) && $accessToken !== '') {
                $expiresIn = (int) ($payload['expiresIn'] ?? 1800);
                $ttlSeconds = max(1, $expiresIn - 30);

                Cache::store('redis')->put(self::REDIS_TOKEN_KEY, $accessToken, $ttlSeconds);

                LoggerService::info('DIC API access token cached in Redis', [
                    'ttl_seconds' => $ttlSeconds,
                    'expires_in_reported' => $expiresIn,
                ]);

                $token = $accessToken;
            } else {
                LoggerService::error('DIC API token response missing accessToken', [
                    'response_body' => $response->body(),
                ]);
            }
        } elseif ($response !== null) {
            LoggerService::error('DIC API token request failed', [
                'status' => $response->status(),
                'response_body' => $response->body(),
            ]);
        }

        return $token;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function sendWithBearer(string $method, string $fullUrl, string $token, array $data = []): Response
    {
        $client = $this->buildBearerClient($token);

        return match (strtoupper($method)) {
            'GET' => $client->get($fullUrl, $data),
            'POST' => $client->asJson()->post($fullUrl, $data),
            default => throw new InvalidArgumentException("Unsupported DIC HTTP method: {$method}"),
        };
    }

    private function buildBearerClient(string $token): PendingRequest
    {
        return Http::withToken($token)
            ->timeout($this->apiTimeout)
            ->retry(
                times: 5,
                sleepMilliseconds: app()->runningUnitTests() ? 1 : 10000,
                when: function ($exception) {
                    if ($exception instanceof ConnectionException) {
                        $isTimeout = str_contains($exception->getMessage(), 'timeout') ||
                            str_contains($exception->getMessage(), 'timed out');

                        LoggerService::warning('DIC API retry attempt', [
                            'reason' => $isTimeout ? 'timeout' : 'connection_error',
                            'exception_message' => $exception->getMessage(),
                        ]);

                        return true;
                    }

                    return false;
                },
                throw: false
            )
            ->acceptJson();
    }
}
