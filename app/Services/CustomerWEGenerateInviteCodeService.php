<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class CustomerWEGenerateInviteCodeService extends BaseService
{
    public static function getCustomerInviteCode()
    {
        $inviteCodeGenerateEndPoint = config('constants.BERLIN_API_ENDPOINT').'/auth/generate-code';
        $inviteCodeGenerateUserName = config('constants.BERLIN_BASIC_AUTH_USER_NAME');
        $inviteCodeGeneratePassword = config('constants.BERLIN_BASIC_AUTH_PASSWORD');

        $inviteCodeGeneratauthBasic = base64_encode($inviteCodeGenerateUserName.':'.$inviteCodeGeneratePassword);
        $clientBerlin = new \GuzzleHttp\Client();

        try {
            $berlinRequest = $clientBerlin->post(
                $inviteCodeGenerateEndPoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Authorization' => 'Basic '.$inviteCodeGeneratauthBasic,
                    ],
                ]
            );

            if ($berlinRequest->getStatusCode() == 200) {
                $getdecodeContents = json_decode($berlinRequest->getBody());
                $getResponseInviteCode = $getdecodeContents->data->inviteCode;
            }
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $responseErrorCode = $e->getResponse()->getStatusCode();
            Log::error('CustomerWEGenerateInviteCodeService Error: '.$responseErrorCode);
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
}
