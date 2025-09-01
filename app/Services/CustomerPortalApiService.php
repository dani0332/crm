<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Logger\LoggerService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Customer Portal API Service
 *
 * Service for handling Customer Portal API requests
 * Manages customer portal integrations, account management, and customer-facing operations
 */
class CustomerPortalApiService
{
    private $client = null;
    private $baseUrl = null;

    private const CUSTOMER_PORTAL_API_EXCEPTION_MESSAGE = 'Customer Portal API Service Exception';

    /**
     * Setup HTTP client with Customer Portal API credentials
     */
    public function __construct()
    {
        $this->baseUrl = config('constants.CUSTOMER_PORTAL_API_ENDPOINT');

        $this->client = Http::withBasicAuth(
            config('constants.CUSTOMER_PORTAL_API_USER'),
            config('constants.CUSTOMER_PORTAL_API_PWD')
        )
        ->withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'x-api-token' => config('constants.CUSTOMER_PORTAL_API_TOKEN'),
        ])
        ->timeout((int) config('constants.CUSTOMER_PORTAL_API_TIMEOUT'));
    }

    /**
     * Send request to Customer Portal API
     *
     * @param string $path API endpoint path
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param array $data Request payload
     * @param bool $isCustomerOperation Whether this is a customer-facing operation
     * @return object
     * @throws \Exception
     */
    public function request(string $path, string $method = 'post', array $data = [], bool $isCustomerOperation = false): object
    {
        $url = $this->baseUrl . $path;

        LoggerService::info('Customer Portal API Request initiated', extra: [
            'url' => $url,
            'method' => strtoupper($method),
            'payload_size' => count($data),
            'is_customer_operation' => $isCustomerOperation,
        ]);

        $response = $this->client
            ->withBody(json_encode($data), 'application/json')
            ->send($method, $url)
            ->onError(function (Response $response) use ($data, $url, $isCustomerOperation) {
                $errorMessage = $response->json()['msg'] ??
                               $response->json()['message'] ??
                               $response->json()['error'] ??
                               self::CUSTOMER_PORTAL_API_EXCEPTION_MESSAGE;

                // Log all 4XX and 5XX errors, and customer operations
                if ($response->status() >= 400 || $isCustomerOperation) {
                    LoggerService::error(self::CUSTOMER_PORTAL_API_EXCEPTION_MESSAGE, extra: [
                        'data' => $data,
                        'url' => $url,
                        'response_status' => $response->getStatusCode(),
                        'response_message' => $errorMessage,
                        'is_customer_operation' => $isCustomerOperation,
                        'response_body' => $response->body(),
                        'jsonResponse' => $response->json(),
                    ]);

                    if ($errorMessage) {
                        vAbort($errorMessage);
                    } else {
                        vAbort(self::CUSTOMER_PORTAL_API_EXCEPTION_MESSAGE);
                    }
                }
            });

        LoggerService::info('Customer Portal API Request completed successfully', extra: [
            'url' => $url,
            'response_status' => $response->status(),
            'response_size' => strlen($response->body()),
        ]);

        return (object) $response->json();
    }

    /**
     * Create customer account in portal
     *
     * @param array $customerData Customer information
     * @return object
     */
    public function createCustomerAccount(array $customerData): object
    {
        $payload = [
            'customerData' => $customerData,
            'timestamp' => now()->toISOString(),
        ];

        return $this->request('/api/v1/customers', 'post', $payload, true);
    }

    /**
     * Update customer account information
     *
     * @param string $customerId Customer ID
     * @param array $customerData Updated customer data
     * @return object
     */
    public function updateCustomerAccount(string $customerId, array $customerData): object
    {
        $payload = [
            'customerId' => $customerId,
            'customerData' => $customerData,
            'timestamp' => now()->toISOString(),
        ];

        return $this->request("/api/v1/customers/{$customerId}", 'put', $payload, true);
    }

    /**
     * Get customer portal information
     *
     * @param string $customerId Customer ID
     * @return object
     */
    public function getCustomerInfo(string $customerId): object
    {
        return $this->request("/api/v1/customers/{$customerId}", 'get', [], true);
    }

    /**
     * Sync customer policies to portal
     *
     * @param string $customerId Customer ID
     * @param array $policies Customer policies
     * @return object
     */
    public function syncCustomerPolicies(string $customerId, array $policies): object
    {
        $payload = [
            'customerId' => $customerId,
            'policies' => $policies,
            'timestamp' => now()->toISOString(),
        ];

        return $this->request('/api/v1/customers/policies', 'post', $payload, true);
    }

    /**
     * Send customer notification via portal
     *
     * @param string $customerId Customer ID
     * @param array $notificationData Notification data
     * @return object
     */
    public function sendCustomerNotification(string $customerId, array $notificationData): object
    {
        $payload = [
            'customerId' => $customerId,
            'notificationData' => $notificationData,
            'timestamp' => now()->toISOString(),
        ];

        return $this->request('/api/v1/notifications', 'post', $payload, true);
    }

    /**
     * Update customer portal preferences
     *
     * @param string $customerId Customer ID
     * @param array $preferences Customer preferences
     * @return object
     */
    public function updateCustomerPreferences(string $customerId, array $preferences): object
    {
        $payload = [
            'customerId' => $customerId,
            'preferences' => $preferences,
            'timestamp' => now()->toISOString(),
        ];

        return $this->request("/api/v1/customers/{$customerId}/preferences", 'put', $payload, true);
    }

    /**
     * Ping Customer Portal API to check service availability
     *
     * @return bool
     */
    public function ping(): bool
    {
        try {
            $response = $this->request('/api/v1/ping', 'get');
            return isset($response->status) && $response->status === 'ok';
        } catch (\Exception $e) {
            LoggerService::warning('Customer Portal API ping failed', extra: [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get Customer Portal API service health status
     *
     * @return array
     */
    public function getHealthStatus(): array
    {
        try {
            $response = $this->request('/api/v1/health', 'get');
            return [
                'status' => 'healthy',
                'response' => $response,
                'timestamp' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ];
        }
    }
}
