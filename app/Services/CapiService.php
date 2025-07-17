<?php

namespace App\Services;

use App\Services\Logger\LoggerService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class CapiService
{
    private $client = null;
    private $baseUrl = null;

    private const CAPI_EXCEPTION_MESSAGE = 'CAPI Service Exception';

    /**
     * setup http client with credentials.
     */
    public function __construct()
    {
        $this->baseUrl = config('constants.CENTRAL_API_ENDPOINT');

        $this->client = Http::withBasicAuth(config('constants.CENTRAL_API_USER'), config('constants.CENTRAL_API_PWD'))
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'x-api-token' => config('constants.CENTRAL_API_TOKEN'),

            ])->timeout(config('constants.CENTRAL_API_TIMEOUT'));
    }

    /**
     * send request to ken.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response
     *
     * @throws \Exception
     */
    public function request($path, $method = 'post', $data = [], $renewal = false)
    {
        $url = $this->baseUrl.$path;
        $response = $this->client->withBody(json_encode($data), 'application/json')->send($method, $url)->onError(function (Response $response) use ($data, $url, $renewal) {
            $errorMessage = $response->json()['msg'] ?? $response->json()['message'] ?? self::CAPI_EXCEPTION_MESSAGE;
            // Only log 5XX errors
            if ($response->status() >= 500 || $renewal) {
                LoggerService::error(self::CAPI_EXCEPTION_MESSAGE, extra: [
                    'data' => $data,
                    'url' => $url,
                    'response_status' => $response ? $response->getStatusCode() : null,
                    'response_message' => $errorMessage,
                    'renewal' => $renewal,
                    'response' => $response,
                    'jsonResponse' => $response->json(),
                ]);

                if ($errorMessage) {
                    vAbort($errorMessage);
                } else {
                    vAbort(self::CAPI_EXCEPTION_MESSAGE);
                }
            }
        });

        return (object) $response->json();
    }
}
