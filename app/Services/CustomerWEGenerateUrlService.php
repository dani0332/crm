<?php

namespace App\Services;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class CustomerWEGenerateUrlService extends BaseService
{
	public static function getCustomerWeUrl()
    {
        $magicUrlGenerateEndPoint = Config::get('constants.BERLIN_API_ENDPOINT').'/auth/generate-url';
        $magicUrlGenerateUserName = Config::get('constants.BERLIN_BASIC_AUTH_USER_NAME');
        $magicUrlGeneratePassword = Config::get('constants.BERLIN_BASIC_AUTH_PASSWORD');

        $magicUrlGeneratauthBasic = base64_encode($magicUrlGenerateUserName . ":" . $magicUrlGeneratePassword);
        $clientBerlin = new \GuzzleHttp\Client();

        try {
            $berlinRequest = $clientBerlin->post(
                $magicUrlGenerateEndPoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Authorization' => 'Basic ' . $magicUrlGeneratauthBasic
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
            //Log::error($e->getLine() ." ".$e->getMessage() ." ".$e->getFile());
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
