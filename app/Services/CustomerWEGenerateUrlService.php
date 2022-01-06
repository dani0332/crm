<?php

namespace App\Services;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class CustomerWEGenerateUrlService extends BaseService
{
	public static function getCustomerWeUrl(Request $request)
    {
        $berlinApiEndPoint = Config::get('constants.BERLIN_API_ENDPOINT');
        $clientBerlin = new \GuzzleHttp\Client();

        try {
            $berlinRequest = $clientBerlin->post(
                $berlinApiEndPoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json', 'Accept' => 'application/json'
                    ],
                ]
            );

            $getStatusCode = $berlinRequest->getStatusCode();

            if ($getStatusCode == 200) {
                $getContents = $berlinRequest->getBody();
                $getdecodeContents = json_decode($getContents);
                $getResponseUrl = $getdecodeContents->data->url;
            }
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $responseErrorCode = $e->getResponse()->getStatusCode();
        }

        if(isset($getResponseUrl)) {
            $apiResponse = $getResponseUrl;
        }
        else {
            if(isset($responseErrorCode)) {
                $apiResponse = $responseErrorCode;
            }
        }
        return $apiResponse;
    }
}
