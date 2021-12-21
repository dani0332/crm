<?php

namespace App\Services;
use Config;

class CapiRequestService
{
    public static function sendCAPIRequest($endpoint, $data)
    {
        $apiEndPoint = Config::get('constants.CENTRAL_API_ENDPOINT') . $endpoint;
        $apiToken = Config::get('constants.CENTRAL_API_TOKEN');
        $apiTimeout = Config::get('constants.CENTRAL_API_TIMEOUT');

        $client = new \GuzzleHttp\Client();
        $capiRequest = $client->post(
            $apiEndPoint,

            [
                'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'x-api-token' => $apiToken],
                'body' => json_encode($data),
                'timeout' => $apiTimeout,
            ]
        );

        $getStatusCode = $capiRequest->getStatusCode();

        if ($getStatusCode == 200) {
            $getContents = $capiRequest->getBody();
            $getdecodeContents = json_decode($getContents);
            return $getdecodeContents;
        } else {
            return "API failed";
        }
    }
}
