<?php

declare(strict_types=1);

namespace App\Services\MetLife;

use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Support\Facades\Http;

class MetLifeRequestService
{
    private string $baseUrl;
    private string $apiVersion;
    private int $timeout;

    public function __construct(string $baseUrl, string $apiVersion, int $timeout)
    {
        $this->baseUrl = $baseUrl;
        $this->apiVersion = $apiVersion;
        $this->timeout = $timeout;
    }

    public function makeRequest(string $endpoint, string $method = 'GET', array $data = [], array $headers = []): array
    {
        try {
            LoggerService::info('MetLife Request - Making API request', [
                'endpoint' => $endpoint,
                'method' => $method
            ]);

            $response = Http::baseUrl($this->baseUrl)
                ->timeout($this->timeout)
                ->withHeaders($headers);

            if ($method === 'GET') {
                $response = $response->get("/api/v{$this->apiVersion}{$endpoint}");
            } else {
                $response = $response->post("/api/v{$this->apiVersion}{$endpoint}", $data);
            }

            if ($response->successful()) {
                LoggerService::info('MetLife Request - Request successful', [
                    'endpoint' => $endpoint,
                    'status_code' => $response->status()
                ]);

                return [
                    'status' => true,
                    'data' => $response->json(),
                    'message' => 'Request successful',
                    'status_code' => $response->status()
                ];
            }

            LoggerService::warning('MetLife Request - Request failed', [
                'endpoint' => $endpoint,
                'status_code' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'status' => false,
                'message' => 'Request failed',
                'error' => $response->body(),
                'status_code' => $response->status()
            ];

        } catch (Exception $e) {
            LoggerService::warning('MetLife Request - Request exception', [
                'endpoint' => $endpoint,
                'method' => $method,
                'error' => $e->getMessage()
            ]);

            return [
                'status' => false,
                'message' => 'Request failed',
                'error' => $e->getMessage()
            ];
        }
    }

    public function buildAuthHeaders(string $sessionId, string $csrfToken): array
    {
        return [
            'x-session-id' => $sessionId,
            'X-CSRFToken' => $csrfToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
    }

    public function buildStandardHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
    }

    public function checkConnectivity(): array
    {
        return $this->makeRequest('/init/', 'GET', [], $this->buildStandardHeaders());
    }
}