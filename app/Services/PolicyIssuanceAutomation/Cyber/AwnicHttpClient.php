<?php

namespace App\Services\PolicyIssuanceAutomation\Cyber;

use App\Enums\ApplicationStorageEnums;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AwnicHttpClient
{
    private string $baseUrl;
    private array $baseHeaders;
    private int $apiTimeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('constants.AWNIC_API_BASE_URL'), '/');
        $this->apiTimeout = (int) app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::AWNI_CYBER_AUTOMATION_API_TIMEOUT);
        $this->baseHeaders = [
            'Partner-Id' => config('constants.AWNIC_API_PARTNER_ID'),
            'Api-Key' => config('constants.AWNIC_API_SECRET_KEY'),
            'Content-Type' => 'application/json',
            'Accept' => '*/*',
        ];
    }

    /**
     * Perform a POST request against AWNI
     */
    public function post(string $endPoint, array $payload = [], array $headers = []): Response
    {
        $url = $this->baseUrl.$endPoint;
        $request = $this->buildClient($headers);

        LoggerService::info('Initiating AWNIC API call', extra: [
            'endpoint' => $endPoint,
            'url' => $url,
            'timeout' => $this->apiTimeout,
            'payload_keys' => array_keys($payload),
        ]);

        try {
            $response = $request->post($url, $payload);

            if ($response->failed()) {
                LoggerService::error('AWNIC API HTTP error response', extra: [
                    'endpoint' => $endPoint,
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                ]);
            } else {
                LoggerService::info('AWNIC API call completed', extra: [
                    'endpoint' => $endPoint,
                    'status_code' => $response->status(),
                ]);
            }

            return $response;
        } catch (Exception $ex) {
            LoggerService::error('AWNIC API call exception', extra: [
                'endpoint' => $endPoint,
                'payload_keys' => array_keys($payload),
            ], exception: $ex);

            throw $ex;
        }
    }

    private function buildClient(array $headers = []): PendingRequest
    {
        return Http::timeout($this->apiTimeout)
            ->withHeaders(array_merge($this->baseHeaders, $headers))
            ->asJson();
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
