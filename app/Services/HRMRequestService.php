<?php

namespace App\Services;

use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Support\Facades\Http;

class HRMRequestService
{
    /**
     * Send HTTP request to HRM API
     *
     * @param string $endpointPath The API endpoint path (e.g., '/v1/employees/codes')
     * @param array $payload The request payload
     * @param string $method HTTP method (default: 'POST')
     * @return array|false Returns decoded response on success, false on failure
     */
    private function sendRequest(string $endpointPath, array $payload = [], string $method = 'POST'): array|false
    {
        try {
            $apiEndPoint = config('constants.HRM_API_ENDPOINT');
            $apiUsername = config('constants.HRM_API_USERNAME');
            $apiPassword = config('constants.HRM_API_PASSWORD');
            $apiTimeout = config('constants.HRM_API_TIMEOUT', 2);

            if (empty($apiEndPoint) || empty($apiUsername) || empty($apiPassword)) {
                LoggerService::error(static::class.'::sendRequest - Missing API configuration', [
                    'endpoint_path' => $endpointPath,
                ]);

                return false;
            }

            $fullUrl = rtrim($apiEndPoint, '/') . '/' . ltrim($endpointPath, '/');

            LoggerService::info(static::class.'::sendRequest - Making API request', [
                'method' => $method,
                'url' => $fullUrl,
                'payload_keys' => array_keys($payload),
                'timeout' => $apiTimeout,
            ]);

            $httpResponse = Http::withBasicAuth($apiUsername, $apiPassword)
                ->timeout($apiTimeout)
                ->acceptJson()
                ->asJson();

            $response = match (strtoupper($method)) {
                'GET' => $httpResponse->get($fullUrl, $payload),
                'POST' => $httpResponse->post($fullUrl, $payload),
                'PUT' => $httpResponse->put($fullUrl, $payload),
                'DELETE' => $httpResponse->delete($fullUrl, $payload),
                default => throw new Exception("Unsupported HTTP method: {$method}")
            };

            $statusCode = $response->status();
            $responseData = $response->json();

            LoggerService::info(static::class.'::sendRequest - API response received', [
                'status_code' => $statusCode,
                'response_status' => $responseData['status'] ?? null,
                'message' => $responseData['message'] ?? null,
                'data_count' => isset($responseData['data']) ? count($responseData['data']) : 0,
            ]);

            if ($response->successful()) {
                if (isset($responseData['status']) && $responseData['status'] === true) {
                    LoggerService::info(static::class.'::sendRequest - API request successful');
                    return $responseData;
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
