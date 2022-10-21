<?php

namespace App\Services;

use Exception;

class SendSmsCustomerService extends BaseService
{
    public function sendSms($customerMobile, $smsText)
    {
        try {
            $smsEndpoint = config('constants.SMS_ENDPOINT');
            $smsSender = config('constants.SMS_SENDER_ID');
            $smsUsername = config('constants.SMS_USERNAME');
            $smsPassword = config('constants.SMS_PASSWORD');

            $client = new \GuzzleHttp\Client();
            $clientRequest = $client->request('POST', $smsEndpoint, ['query' => [
              'username' => $smsUsername,
              'password' => $smsPassword,
              'senderid' => $smsSender,
              'to' => $customerMobile,
              'text' => $smsText,
              'type' => 'text',
            ]]);

            $responseCode = $clientRequest->getStatusCode();
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            info($responseCode);
        }

        return $responseCode;
    }

    public function getShortUrl($url)
    {
        try {
            $smsEndpoint = config('constants.SMS_URL_SHORTNER_ENDPOINT');
            $smsUsername = config('constants.SMS_USERNAME');
            $smsPassword = config('constants.SMS_PASSWORD');

            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];

            $body = json_encode([
                'username' => $smsUsername,
                'password' => $smsPassword,
                'long_url' => $url,
                'type' => 'unique',
            ], JSON_UNESCAPED_SLASHES);

            $client = new \GuzzleHttp\Client();
            $clientRequest = $client->post(
                $smsEndpoint,
                [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 10000,
                ]
            );

            $response = json_decode($clientRequest->getBody()->getContents());
            $response = $response->short_url;
        } catch (Exception $ex) {
            $response = json_encode($ex->getCode().' '.$ex->getMessage());
            info($response);
        }

        return $response;
    }
}
