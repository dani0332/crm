<?php

namespace App\Services;

use App\Services\Logger\LoggerService;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class KenService
{
    private $client = null;
    private $baseUrl = null;

    /**
     * setup http client with credentials.
     */
    public function __construct()
    {
        $this->baseUrl = config('constants.KEN_API_ENDPOINT');

        $this->client = Http::withBasicAuth(config('constants.KEN_API_USER'), config('constants.KEN_API_PWD'))
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'x-api-token' => config('constants.KEN_API_TOKEN'),

            ])->timeout(config('constants.KEN_API_TIMEOUT'));
    }

    /**
     * send request to ken.
     *
     * @return PromiseInterface|Response
     *
     * @throws \Exception
     */
    public function request($path, $method = 'post', $data = [])
    {
        $url = $this->baseUrl.$path;
        $response = $this->client->withBody(json_encode($data), 'application/json')
            ->send($method, $url)->onError(function ($response) use ($data, $url) {
                // Only log 5XX errors
                if ($response->status() >= 500) {
                    LoggerService::error('KEN Service Server Error', [
                        'data' => $data,
                        'url' => $url,
                        'status_code' => $response->status(),
                        'response' => $response->json(),
                    ]);

                    if (isset($response->json()['msg'])) {
                        vAbort($response->json()['msg']);
                    } else {
                        vAbort('KEN Service Exception');
                    }
                }
            });

        return $response->json();
    }

    /**
     * Send a request to KEN and return the raw HTTP client response.
     * Does not abort on 4xx/5xx; use when the caller maps status and body (e.g. API proxies).
     *
     * @param  array<string, mixed>  $data
     */
    public function sendRequest(string $path, string $method = 'post', array $data = []): Response
    {
        $url = $this->baseUrl.$path;

        return $this->client->withBody(json_encode($data), 'application/json')
            ->send($method, $url);
    }

    public function renewalRequest($path, $method = 'post', $data = [])
    {
        $url = config('constants.KEN2_API_ENDPOINT').$path;
        $response = $this->client->withBody(json_encode($data), 'application/json')
            ->send($method, $url)->onError(function ($response) use ($data, $url) {
                // log all errors in renewal request
                LoggerService::error('KEN Service Server Error', [
                    'data' => $data,
                    'url' => $url,
                    'response' => $response,
                    'status_code' => $response->status(),
                    'jsonResponse' => $response->json() ?? $response->body(),
                ]);

                if (isset($response->json()['msg'])) {
                    vAbort($response->json()['msg']);
                } else {
                    vAbort('KEN Service Exception');
                }
            });

        return $response->json();
    }

    /**
     * Submit Sukoon documents after AML success. Does not abort the app on HTTP errors.
     *
     * @return array{success: bool, status_code?: int, body?: mixed, payment_link?: ?string, error?: string}
     */
    public function submitSukoonDocuments(string $quoteUid, string $callSource = 'imcrm'): array
    {
        $path = '/submit-sukoon-documents';
        $url = $this->baseUrl.$path;
        $payload = [
            'quoteUID' => $quoteUid,
            'callSource' => $callSource,
        ];

        try {
            $response = $this->client
                ->withBody(json_encode($payload), 'application/json')
                ->send('post', $url);

            $statusCode = $response->status();
            $json = $response->json();

            LoggerService::info('KEN submit-sukoon-documents response', [
                'quote_uid' => $quoteUid,
                'status_code' => $statusCode,
                'body' => $json,
            ]);

            $paymentLink = null;
            if (is_array($json)) {
                $paymentLink = self::extractPaymentLink($json);
            }

            return [
                'success' => $statusCode >= 200 && $statusCode < 300,
                'status_code' => $statusCode,
                'body' => $json,
                'payment_link' => $paymentLink,
            ];
        } catch (\Throwable $e) {
            LoggerService::error('KEN submit-sukoon-documents exception', [
                'quote_uid' => $quoteUid,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $json
     */
    public static function extractPaymentLink(?array $json): ?string
    {
        if ($json === null || $json === []) {
            return null;
        }

        foreach (['paymentLink', 'payment_link', 'paymentUrl', 'payment_url', 'paymentURL'] as $key) {
            if (! empty($json[$key]) && is_string($json[$key])) {
                return $json[$key];
            }
        }

        if (! empty($json['data']) && is_array($json['data'])) {
            $nested = self::extractPaymentLink($json['data']);
            if ($nested !== null) {
                return $nested;
            }
        }

        return null;
    }
}
