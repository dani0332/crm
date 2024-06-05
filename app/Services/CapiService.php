<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CapiService
{
    private $client = null;
    private $baseUrl = null;

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
    public function request($path, $method = 'post', $data = [])
    {
        $url = $this->baseUrl.$path;
        $response = $this->client->withBody(json_encode($data), 'application/json')->send($method, $url)->onError(function ($response) use ($data, $url) {
            info('CAPI Service Exception', ['data' => $data, 'url' => $url]);
            if (isset($response->json()['msg'])) {
                vAbort($response->json()['msg']);
            } else {
                vAbort('CAPI Service Exception');
            }
        });

        return (object) $response->json();
    }
}
