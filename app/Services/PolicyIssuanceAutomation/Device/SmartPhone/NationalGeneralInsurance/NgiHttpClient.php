<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance;

use App\Enums\ApplicationStorageEnums;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class NgiHttpClient
{
    private string $baseUrl;
    private int $apiTimeout;
    private ?string $authToken = null;

    private const APPLICATION_JSON = 'application/json';

    public function __construct()
    {
        $this->baseUrl = rtrim((string) (config('constants.NGI_API_BASE_URL') ?? ''), '/');
        $this->apiTimeout = (int) app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::NGI_SMARTPHONE_AUTOMATION_API_TIMEOUT) ?: 60;
    }

    /**
     * Authenticate and get bearer token
     */
    public function authenticate(): ?string
    {
        if ($this->authToken) {
            return $this->authToken;
        }

        $authUrl = $this->baseUrl.'/api/Auth/GetToken';
        $payload = [
            'client_code' => config('constants.NGI_API_CLIENT_CODE'),
            'client_id' => config('constants.NGI_API_CLIENT_ID'),
            'client_secret' => config('constants.NGI_API_CLIENT_SECRET'),
        ];

        LoggerService::info('Initiating NGI authentication', extra: [
            'url' => $authUrl,
        ]);

        try {
            $response = Http::timeout($this->apiTimeout)
                ->withHeaders([
                    'Content-Type' => self::APPLICATION_JSON,
                    'Accept' => self::APPLICATION_JSON,
                ])
                ->post($authUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $this->authToken = $data['access_token'] ?? null;

                LoggerService::info('NGI authentication successful');

                return $this->authToken;
            }

            LoggerService::error('NGI authentication failed', extra: [
                'status_code' => $response->status(),
                'response_body' => $response->body(),
            ]);

            return null;
        } catch (Exception $ex) {
            LoggerService::error('NGI authentication exception', exception: $ex);
            throw $ex;
        }
    }

    /**
     * Perform a POST request against NGI API
     */
    public function post(string $endPoint, array $payload = [], array $headers = []): Response
    {
        $url = $this->baseUrl.$endPoint;
        $request = $this->getAuthenticatedClient($headers);

        LoggerService::info('Initiating NGI API POST call', extra: [
            'endpoint' => $endPoint,
            'url' => $url,
            'timeout' => $this->apiTimeout,
            'payload_keys' => array_keys($payload),
        ]);

        try {
            $response = $request->post($url, $payload);

            if ($response->failed()) {
                LoggerService::error('NGI API HTTP error response', extra: [
                    'endpoint' => $endPoint,
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                ]);
            } else {
                LoggerService::info('NGI API call completed', extra: [
                    'endpoint' => $endPoint,
                    'status_code' => $response->status(),
                ]);
            }

            return $response;
        } catch (Exception $ex) {
            LoggerService::error('NGI API call exception', extra: [
                'endpoint' => $endPoint,
                'payload_keys' => array_keys($payload),
            ], exception: $ex);

            throw $ex;
        }
    }

    /**
     * Perform a GET request against NGI API
     */
    public function get(string $endPoint, array $queryParams = [], array $headers = []): Response
    {
        $url = $this->baseUrl.$endPoint;
        if (! empty($queryParams)) {
            $url .= '?'.http_build_query($queryParams);
        }

        $request = $this->getAuthenticatedClient($headers);

        LoggerService::info('Initiating NGI API GET call', extra: [
            'endpoint' => $endPoint,
            'url' => $url,
            'timeout' => $this->apiTimeout,
        ]);

        try {
            $response = $request->get($url);

            if ($response->failed()) {
                LoggerService::error('NGI API HTTP error response', extra: [
                    'endpoint' => $endPoint,
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                ]);
            } else {
                LoggerService::info('NGI API call completed', extra: [
                    'endpoint' => $endPoint,
                    'status_code' => $response->status(),
                ]);
            }

            return $response;
        } catch (Exception $ex) {
            LoggerService::error('NGI API call exception', extra: [
                'endpoint' => $endPoint,
            ], exception: $ex);

            throw $ex;
        }
    }

    /**
     * Get authenticated HTTP client
     */
    public function getAuthenticatedClient(array $headers = []): PendingRequest
    {
        $token = $this->authenticate();

        $defaultHeaders = [
            'Content-Type' => self::APPLICATION_JSON,
            'Accept' => self::APPLICATION_JSON,
        ];

        if ($token) {
            $defaultHeaders['Authorization'] = 'Bearer '.$token;
        }

        return Http::timeout($this->apiTimeout)
            ->withHeaders(array_merge($defaultHeaders, $headers))
            ->asJson();
    }

    /**
     * Get the base URL
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
