<?php

namespace App\Services;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class MarshallService
{
    private $client = null;
    private $baseUrl = null;

    /**
     * setup http client with credentials.
     */
    public function __construct()
    {

        $this->baseUrl = config('constants.MARSHALL_API_ENDPOINT');

        $this->client = Http::withBasicAuth(config('constants.MARSHALL_API_USER'), config('constants.MARSHALL_API_PWD'))
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'x-api-token' => config('constants.MARSHALL_API_TOKEN'),

            ])->timeout(30);
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
                $this->handleError($response, $data, $url);
            });

        return $response->json();
    }

    private function handleError($response, $data, $url)
    {
        $logData = [
            'data' => $data,
            'url' => $url,
            'status' => $response->status(),
            'response' => $response->json(),
        ];

        $logData['msg'] = $response->json()['msg'] ?? $response->json()['message'] ?? 'Capture failed, something went wrong';

        info('Marshall Service Exception', $logData);
        vAbort($logData['msg']);
    }
}
