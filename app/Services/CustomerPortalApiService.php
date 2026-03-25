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
     * @param  string  $path  API endpoint path
     * @param  string  $method  HTTP method (GET, POST, PUT, DELETE)
     * @param  array  $data  Request payload
     * @param  bool  $isCustomerOperation  Whether this is a customer-facing operation
     *
     * @throws \Exception
     */
    public function request(string $path, string $method = 'post', array $data = [], bool $isCustomerOperation = false): object
    {
        $url = $this->baseUrl.$path;

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

}
