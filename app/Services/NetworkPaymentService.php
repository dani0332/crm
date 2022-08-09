<?php

namespace App\Services;

use App\Models\CarQuote;
use Config;
use Illuminate\Support\Facades\Config as FacadesConfig;

class NetworkPaymentService
{
    public static function sendNetworkTokenRequest()
    {
        $apiEndPoint = Config::get('constants.NETWORK_TOKEN_ENDPOINT');
        $apiTimeout = Config::get('constants.NETWORK_REQUEST_TIMEOUT');
        $apiMerchantToken = Config::get('constants.NETWORK_TOKEN_MERCHANT_TOKEN');

        $client = new \GuzzleHttp\Client();
        $networkTokenRequest = $client->post(
            $apiEndPoint,

            [
                'headers' => ['Content-Type' => 'application/vnd.ni-identity.v1+json', 'Accept' => 'application/vnd.ni-identity.v1+json', 'Authorization' => 'Basic '. $apiMerchantToken],
                'timeout' => $apiTimeout,
            ]
        );

        return $networkTokenRequest;
    }

    public  static function sendNetworkInvoiceRequest($data, $token)
    {
        $apiEndPoint = Config::get('constants.NETWORK_INVOICE_ENDPOINT') . Config::get('constants.NETWORK_OUTLET_REFERENCE') . '/invoice';
        $apiTimeout = Config::get('constants.NETWORK_REQUEST_TIMEOUT');

        $client = new \GuzzleHttp\Client();
        $networkCreateInvoiceRequest = $client->post(
            $apiEndPoint,

            [
                'headers' => [
                    'Origin' => Config::get('constants.NETWORK_CORS_DOMAIN'),
                    'Accept' => 'application/vnd.ni-invoice.v1+json',
                    'Content-Type' => 'application/vnd.ni-invoice.v1+json',
                    'Authorization' => 'Bearer ' . $token
                ],
                'timeout' => $apiTimeout,
                'json' => $data,
            ]
        );

        return $networkCreateInvoiceRequest;
    }
}
