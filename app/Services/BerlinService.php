<?php

namespace App\Services;

use App\Models\MyAlFredUser;
use Illuminate\Support\Facades\Log;

class BerlinService extends BaseService
{
    private $berlinEndpoint,
    $berlinUserName,
    $berlinAuthPassword;

    public function __construct()
    {
        $this->berlinEndpoint = config('constants.BERLIN_API_ENDPOINT');
        $this->berlinUserName = config('constants.BERLIN_BASIC_AUTH_USER_NAME');
        $this->berlinAuthPassword = config('constants.BERLIN_BASIC_AUTH_PASSWORD');
    }

    public function getCustomerInviteCode()
    {
        $this->berlinEndpoint .= '/auth/generate-code';

        $inviteCodeGeneratauthBasic = base64_encode($this->berlinUserName.':'.$this->berlinAuthPassword);
        $clientBerlin = new \GuzzleHttp\Client();

        try {
            $berlinRequest = $clientBerlin->post(
                $this->berlinEndpoint,
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
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
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
        $clientBerlin = new \GuzzleHttp\Client();

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
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
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

    public function extendCustomerSubscription($customerId)
    {
        $this->berlinEndpoint .= '/auth/extend-subscription';

        $customer = MyAlFredUser::select('signup_url', 'code')->where('customer_id', $customerId)->latest()->first();

        $customerDataArr = json_encode([
            'token' => $customer->code,
            'isToken' => isset($customer->signup_url) ? true : false,
        ]);

        $magicUrlGeneratauthBasic = base64_encode($this->berlinUserName.':'.$this->berlinAuthPassword);
        $clientExtendSubscription = new \GuzzleHttp\Client();

        try {
            $requestExtendSubscription = $clientExtendSubscription->post(
                $this->berlinEndpoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Authorization' => 'Basic '.$magicUrlGeneratauthBasic,
                    ],
                    'body' => $customerDataArr,
                ]
            );

            $apiResponse = $requestExtendSubscription->getStatusCode();
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $apiResponse = $e->getResponse()->getStatusCode();
            Log::error('Berlin Service - extendCustomerSubscription Error: '.$apiResponse);
        }

        return $apiResponse;
    }
}
