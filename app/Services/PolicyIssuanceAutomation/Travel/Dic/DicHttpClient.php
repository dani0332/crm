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
    /** Laravel Redis cache key — value must be set via e.g. `Cache::store('redis')->put('dic-token', $bearer, $ttl)`. */
    private const REDIS_TOKEN_KEY = 'dic-token';

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
                $token = $this->getBearerToken();
                if ($token === null) {
                    return null;
                }
                $response = $this->sendWithBearer($method, $fullUrl, $token, $data);
            }

            if ($response->failed()) {
                LoggerService::error('DIC API HTTP error response', [
                    'method' => strtoupper($method),
                    'status_code' => $response->status(),
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

        return is_string($value) && $value !== '' ? $value : null;
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
