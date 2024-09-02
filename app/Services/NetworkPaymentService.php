<?php

namespace App\Services;

class NetworkPaymentService
{
    public static function sendNetworkTokenRequest()
    {
        $apiEndPoint = config('constants.NETWORK_TOKEN_ENDPOINT');
        $apiTimeout = config('constants.NETWORK_REQUEST_TIMEOUT');
        $apiMerchantToken = config('constants.NETWORK_TOKEN_MERCHANT_TOKEN');

        $client = new \GuzzleHttp\Client;
        $networkTokenRequest = $client->post(
            $apiEndPoint,

            [
                'headers' => ['Content-Type' => 'application/vnd.ni-identity.v1+json', 'Accept' => 'application/vnd.ni-identity.v1+json', 'Authorization' => 'Basic '.$apiMerchantToken],
                'timeout' => $apiTimeout,
            ]
        );

        return $networkTokenRequest;
    }

    public static function sendNetworkInvoiceRequest($data, $token)
    {
        $apiEndPoint = config('constants.NETWORK_INVOICE_ENDPOINT').config('constants.NETWORK_OUTLET_REFERENCE').'/invoice';
        $apiTimeout = config('constants.NETWORK_REQUEST_TIMEOUT');

        $client = new \GuzzleHttp\Client;
        $networkCreateInvoiceRequest = $client->post(
            $apiEndPoint,

            [
                'headers' => [
                    'Origin' => config('constants.NETWORK_CORS_DOMAIN'),
                    'Accept' => 'application/vnd.ni-invoice.v1+json',
                    'Content-Type' => 'application/vnd.ni-invoice.v1+json',
                    'Authorization' => 'Bearer '.$token,
                ],
                'timeout' => $apiTimeout,
                'json' => $data,
            ]
        );

        return $networkCreateInvoiceRequest;
    }
}
