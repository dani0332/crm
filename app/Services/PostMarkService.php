<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class PostMarkService extends BaseService
{
    public function sendEmail($body)
    {
        try {
            $headers = [
                'Accept' => 'application/json',
                'X-Postmark-Server-Token' => config('constants.POSTMARK_TOKEN'),
                'Content-Type' => 'application/json',
            ];

            $client = new \GuzzleHttp\Client;
            $clientRequest = $client->post(
                config('constants.POSTMARK_URL'),
                [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 100,
                ]
            );

            $response = json_decode(json_encode($clientRequest->getStatusCode().' '.$clientRequest->getBody()->getContents()), true);
            $responseCode = $clientRequest->getStatusCode();
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $responseDetail = 'PostMark Send Email: Code/Message: '.$responseCode.'/'.$ex->getMessage().' Class: '.get_class();
            Log::error($responseDetail);
            $response = json_encode($ex->getCode().' '.$ex->getMessage());
        }

        return $responseCode;
    }
}
