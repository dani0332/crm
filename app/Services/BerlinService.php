<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Support\Facades\Log;

class BerlinService extends BaseService
{
    private $berlinEndpoint;
    private $berlinUserName;
    private $berlinAuthPassword;
    private $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->berlinEndpoint = config('constants.BERLIN_API_ENDPOINT');
        $this->berlinUserName = config('constants.BERLIN_BASIC_AUTH_USER_NAME');
        $this->berlinAuthPassword = config('constants.BERLIN_BASIC_AUTH_PASSWORD');
        $this->customerService = $customerService;
    }

    public function getCustomerInviteCode()
    {
        $inviteCodeGeneratauthBasic = base64_encode($this->berlinUserName.':'.$this->berlinAuthPassword);
        $clientBerlin = new Client;

        try {
            $berlinRequest = $clientBerlin->post(
                $this->berlinEndpoint.'/auth/generate-code',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Authorization' => 'Basic '.$inviteCodeGeneratauthBasic,
                    ],
                    'timeout' => 10,
                ]
            );

            if ($berlinRequest->getStatusCode() == 200) {
                $getdecodeContents = json_decode($berlinRequest->getBody());
                $getResponseInviteCode = $getdecodeContents->data->inviteCode;
            }
        } catch (BadResponseException $e) {
            $responseErrorCode = $e->getResponse()->getStatusCode();
            Log::error('Berlin Service - InviteCodeService Error: '.$responseErrorCode);
        }

        if (isset($getResponseInviteCode)) {
            $apiResponse = $getResponseInviteCode;
        } else {
            if (isset($responseErrorCode)) {
                $apiResponse = $responseErrorCode;
            }
        }

        return $apiResponse;
    }

    public static function getCustomerWeUrl()
    {
        $magicUrlGenerateEndPoint = config('constants.BERLIN_API_ENDPOINT').'/auth/generate-url';
        $magicUrlGenerateUserName = config('constants.BERLIN_BASIC_AUTH_USER_NAME');
        $magicUrlGeneratePassword = config('constants.BERLIN_BASIC_AUTH_PASSWORD');

        $magicUrlGeneratauthBasic = base64_encode($magicUrlGenerateUserName.':'.$magicUrlGeneratePassword);
        $clientBerlin = new Client;

        try {
            $berlinRequest = $clientBerlin->post(
                $magicUrlGenerateEndPoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Authorization' => 'Basic '.$magicUrlGeneratauthBasic,
                    ],
                ]
            );

            if ($berlinRequest->getStatusCode() == 200) {
                $getdecodeContents = json_decode($berlinRequest->getBody());
                $getResponseUrl = $getdecodeContents->data->url;
            }
        } catch (BadResponseException $e) {
            $responseErrorCode = $e->getResponse()->getStatusCode();
        }

        if (isset($getResponseUrl)) {
            $apiResponse = $getResponseUrl;
        } else {
            if (isset($responseErrorCode)) {
                $apiResponse = $responseErrorCode;
            }
        }

        return $apiResponse;
    }
}
