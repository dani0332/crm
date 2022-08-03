<?php

namespace App\Services;

class HttpRequestService extends BaseService
{
    public function processRequest($data, $creds)
    {
        $authBasic = base64_encode($creds['apiUserName'].':'.$creds['apiPassword']);

        $kenClient = new \GuzzleHttp\Client();

        try {
            $kenRequest = $kenClient->post(
                $creds['apiEndPoint'],
                [
                    'headers' => [
                        'Content-Type' => 'application/json', 'Accept' => 'application/json',
                        'x-api-token' => $creds['apiToken'],
                        'Authorization' => 'Basic '.$authBasic,
                    ],
                    'body' => json_encode($data),
                    'timeout' => $creds['apiTimeout'],
                ]
            );

            $statusCode = $kenRequest->getStatusCode();

            return $statusCode;
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $response = json_decode((string) $e->getResponse()->getBody());

            if (isset($response->error)) {
                $response = $response->error;
            }
            if (isset($response->msg)) {
                $response = $response->msg;
            }

            return $response;
        }
    }
}
