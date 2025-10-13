<?php

declare(strict_types=1);

namespace App\Services\MetLife;

use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class MetLifeRequestService
{
    private string $baseUrl;
    private int $timeout;

    public function __construct(string $baseUrl, int $timeout)
    {
        $this->baseUrl = $baseUrl;
        $this->timeout = $timeout;
    }

    public function makeRequest(string $endpoint, string $method = 'GET', array $data = [], array $headers = []): array
    {
        try {
            LoggerService::info('MetLife API request', [
                'endpoint' => $endpoint,
                'method' => $method,
                'url' => $this->baseUrl.$endpoint,
            ]);

            $http = Http::baseUrl($this->baseUrl)
                ->timeout($this->timeout)
                ->withHeaders($headers);

            $response = match (strtoupper($method)) {
                'GET' => $http->get($endpoint, $data),
                'POST' => $http->post($endpoint, $data),
                default => throw new Exception("Unsupported HTTP method: {$method}"),
            };

            LoggerService::info('MetLife API response', [
                'endpoint' => $endpoint,
                'status_code' => $response->status(),
                'successful' => $response->successful(),
            ]);

            return $this->handleResponse($response, $endpoint);

        } catch (Exception $e) {
            LoggerService::warning('MetLife request exception', [
                'endpoint' => $endpoint,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'MetLife operation failed: '.$e->getMessage(),
                'data' => ['exception_code' => $e->getCode()],
            ];
        }
    }

    public function buildHeaders(?string $sessionId = null, ?string $csrfToken = null): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($sessionId && $csrfToken) {
            $headers['x-session-id'] = $sessionId;
            $headers['X-CSRFToken'] = $csrfToken;
        }

        return $headers;
    }

    private function handleResponse(Response $response, string $endpoint): array
    {
        $statusCode = $response->status();
        $responseBody = $response->json() ?? [];

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'Request successful',
                'data' => ['status_code' => $statusCode, 'data' => $responseBody],
            ];
        }

        $errorMessage = $responseBody['message'] ?? $response->body() ?? 'Request failed';
        LoggerService::warning('MetLife HTTP request failed', [
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'error' => $errorMessage,
        ]);

        return [
            'success' => false,
            'message' => "Request failed (Status: {$statusCode}): {$errorMessage}",
            'data' => ['status_code' => $statusCode, 'endpoint' => $endpoint],
        ];
    }
}
