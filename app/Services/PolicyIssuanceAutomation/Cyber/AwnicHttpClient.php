<?php

namespace App\Services\PolicyIssuanceAutomation\Cyber;

use App\Enums\ApplicationStorageEnums;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class AwnicHttpClient
{
    private string $baseUrl;
    private array $baseHeaders;
    private array $headers = [];
    private int $apiTimeout;
    private string $className = 'AwnicHttpClient';

    public function __construct()
    {
        $this->baseUrl = config('constants.AWNI_API_BASE_URL');
        $this->apiTimeout = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::AWNI_CYBER_AUTOMATION_API_TIMEOUT);
        $this->baseHeaders = [
            'Partner-Id' => config('constants.AWNI_API_PARTNER_ID'),
            'Api-Key' => config('constants.AWNI_API_SECRET_KEY'),
            'Content-Type' => 'application/json',
        ];
        $this->headers = $this->baseHeaders;
    }

    /**
     * Make HTTP POST call to AWNI API
     *
     * @param string $endPoint API endpoint path
     * @param array $payload Request payload
     * @param string $keyAPI API key identifier for logging
     * @return array Response with status, data, error, and message
     */
    public function post(string $endPoint, array $payload, string $keyAPI): array
    {
        $headers = $this->prepareHeaders($keyAPI);
        $url = $this->baseUrl . $endPoint;
        $timeOut = $this->resolveTimeout();

        LoggerService::info('Initiating API call', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'api_key' => $keyAPI,
            'endpoint' => $endPoint,
            'url' => $url,
            'timeout' => $timeOut,
            'payload_keys' => array_keys($payload),
        ]);

        $response = ['status' => false, 'error' => null, 'message' => null, 'data' => null, 'completed_step' => null];

        try {
            $httpResponse = Http::timeout($timeOut)->withHeaders($headers)->post($url, $payload);
            $statusCode = $httpResponse->status();
            $responseObject = $httpResponse->object();

            LoggerService::info('API response received', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
                'api_key' => $keyAPI,
                'status_code' => $statusCode,
                'response_size_bytes' => strlen($httpResponse->body()),
                'has_error_list' => isset($responseObject?->errorList),
                'is_success' => $responseObject?->isSuccess ?? null,
            ]);

            if (in_array($statusCode, [JsonResponse::HTTP_OK, JsonResponse::HTTP_CREATED])) {
                if (
                    $responseObject == null ||
                    isset($responseObject?->errorList) ||
                    (isset($responseObject?->isSuccess) && $responseObject?->isSuccess == 'N')
                ) {
                    $response['error'] = $responseObject?->errorList ?? $keyAPI . ' API Failed';
                    $response['data'] = $responseObject == null ? null : '';
                    $response['message'] = $this->extractErrorMessage($responseObject, $keyAPI);

                    LoggerService::warning('API call failed despite 2xx status', extra: [
                        'class' => $this->className,
                        'function' => __FUNCTION__,
                        'api_key' => $keyAPI,
                        'status_code' => $statusCode,
                        'error' => $response['error'],
                        'message' => $response['message'],
                    ]);
                } else {
                    $response['status'] = true;
                    $response['data'] = $responseObject;
                    $response['message'] = 'API call successfully executed.';

                    LoggerService::info('API call successful', extra: [
                        'class' => $this->className,
                        'function' => __FUNCTION__,
                        'api_key' => $keyAPI,
                        'status_code' => $statusCode,
                    ]);
                }
            } elseif (isset($responseObject?->statusCode) && $responseObject?->statusCode == JsonResponse::HTTP_NOT_FOUND) {
                $response['error'] = '404 Not Found';
                $response['message'] = '404 Not Found';

                LoggerService::error('API endpoint not found', extra: [
                    'class' => $this->className,
                    'function' => __FUNCTION__,
                    'api_key' => $keyAPI,
                    'endpoint' => $endPoint,
                    'status_code' => $statusCode,
                ]);
            } else {
                $response['error'] = $keyAPI . ' API Failed';
                $response['message'] = 'There is an Exception on AWNI API call.';

                LoggerService::error('Unexpected API response', extra: [
                    'class' => $this->className,
                    'function' => __FUNCTION__,
                    'api_key' => $keyAPI,
                    'status_code' => $statusCode,
                    'response_body' => $httpResponse->body(),
                ]);
            }
        } catch (Exception $ex) {
            LoggerService::error('Exception during API call', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
                'api_key' => $keyAPI,
                'endpoint' => $endPoint,
                'line_number' => $ex->getLine(),
            ], exception: $ex);

            $response['error'] = $ex->getMessage();
            $response['message'] = $ex->getMessage();
        }

        return $response;
    }

    /**
     * Prepare headers for specific API call
     *
     * @param string $keyAPI API key identifier
     * @return array Headers array
     */
    private function prepareHeaders(string $keyAPI): array
    {
        $headers = $this->headers ?: $this->baseHeaders;

        if ($keyAPI === 'PolicyResponse') {
            $headers['TP-Payment-Key'] = 'TP_PAYMENT';
            $headers['Accept'] = 'application/json';
        } else {
            $headers['Accept'] = '*/*';
        }

        $this->headers = $headers;

        return $headers;
    }

    /**
     * Resolve API timeout value
     *
     * @return int Timeout in seconds
     */
    private function resolveTimeout(): int
    {
        return (int) ($this->apiTimeout ?? 30);
    }

    /**
     * Extract error message from API response object
     *
     * @param \stdClass|null $responseObject The API response object
     * @param string $keyAPI The API key to access nested error details
     * @return string|mixed The extracted error message
     */
    private function extractErrorMessage($responseObject, string $keyAPI)
    {
        if (isset($responseObject?->errorList)) {
            return json_encode($responseObject->errorList);
        }

        if (isset($responseObject?->message)) {
            return $responseObject->message;
        }

        // Return entire response object as fallback
        return $responseObject ?? $keyAPI . ' API Failed';
    }

    /**
     * Get base URL
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}

