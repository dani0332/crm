<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Health\Adnic;

use App\Enums\ApplicationStorageEnums;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AdnicHttpClient
{
    private string $baseUrl;
    private array $authParam;
    private array $baseHeaders;
    private int $apiTimeout;
    private string $partnerId;
    private string $partnerReferenceNo;

    public function __construct()
    {
        $this->apiTimeout = (int) app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ADNIC_HEALTH_AUTOMATION_API_TIMEOUT);

        $this->partnerId = (string) (config('constants.ADNIC_PARTNER_ID') ?? '');
        $this->partnerReferenceNo = (string) (config('constants.ADNIC_PARTNER_REFERENCE_NO') ?? '');
        $this->baseUrl = (string) (config('constants.ADNIC_API_BASE_URL') ?? '').'/MedicalProductAPI/MedicalAPI.svc/API/Medical';
        $this->authParam = [
            'Authorization' => (string) (config('constants.ADNIC_AUTHORIZATION_TOKEN') ?? ''),
            'Ocp-Apim-Subscription-Key' => (string) (config('constants.ADNIC_SUBSCRIPTION_KEY') ?? ''),
        ];

        $this->baseHeaders = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Ocp-Apim-Subscription-Key' => $this->authParam['Ocp-Apim-Subscription-Key'],
            'Authorization' => $this->authParam['Authorization'],
        ];
    }

    public function post(string $endPoint, array $payload = [], array $headers = []): Response
    {
        $url = $this->baseUrl.$endPoint;
        $httpClient = $this->buildClient($headers);

        LoggerService::info('Initiating ADNIC API call', extra: [
            'endpoint' => $endPoint,
            'url' => $url,
            'timeout' => $this->apiTimeout,
            'payload_keys' => array_keys($payload),
        ]);

        try {
            $response = $httpClient->post($url, $payload);

            if ($response->failed()) {
                LoggerService::error('ADNIC API HTTP error response', extra: [
                    'endpoint' => $endPoint,
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                ]);
            } else {
                LoggerService::info('ADNIC API call completed', extra: [
                    'endpoint' => $endPoint,
                    'status_code' => $response->status(),
                ]);
            }

            return $response;
        } catch (Exception $ex) {
            LoggerService::error('ADNIC API call exception', extra: [
                'endpoint' => $endPoint,
                'payload_keys' => array_keys($payload),
            ], exception: $ex);

            throw $ex;
        }
    }

    private function buildClient(array $headers = []): PendingRequest
    {
        return Http::timeout($this->apiTimeout)
            ->retry(
                times: 5,
                sleepMilliseconds: app()->runningUnitTests() ? 1 : 10000,
                when: function ($exception) {
                    // Retry on connection and timeout exceptions
                    if ($exception instanceof ConnectionException) {
                        $isTimeout = str_contains($exception->getMessage(), 'timeout') ||
                                    str_contains($exception->getMessage(), 'timed out');

                        LoggerService::warning('ADNIC API retry attempt', extra: [
                            'reason' => $isTimeout ? 'timeout' : 'connection_error',
                            'exception_message' => $exception->getMessage(),
                            'attempt' => 'retrying',
                        ]);

                        return true;
                    }

                    return false;
                },
                // Do not convert failed HTTP responses (4xx/5xx) into exceptions; callers inspect Response.
                // Connection/timeout failures still throw after retries (they never reach this throw path).
                throw: false
            )
            ->withHeaders(array_merge($this->baseHeaders, $headers))
            ->asJson();
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getPartnerId(): string
    {
        return $this->partnerId;
    }

    public function getPartnerReferenceNo(): string
    {
        return $this->partnerReferenceNo;
    }
}
