<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class KyoService
{
    private $client = null;
    private $baseUrl = null;

    public function __construct()
    {
        $this->baseUrl = env('KYO_END_POINT');

        $this->client = Http::// withBasicAuth(config('constants.KEN_API_USER'), config('constants.KEN_API_PWD'))->
        withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            // 'x-api-token' => config('constants.KEN_API_TOKEN'),

        ])->timeout(100);
    }

    /**
     * @return object|null
     *
     * @throws \Exception
     */
    public function request($path, $method = 'get', $data = [])
    {
        $url = $this->baseUrl.$path;

        info('KYO URL : '.$url);

        $response = $this->client->withBody(json_encode($data))
            ->send($method, $url)
            ->onError(function ($response) {});

        return $response->object();
    }

    /**
     * @return object|null
     */
    public function get($path, $data = [])
    {
        return $this->request($path, 'get', $data);
    }

    public function post($path, $data = [])
    {
        return $this->request($path, 'post', $data);
    }
}
